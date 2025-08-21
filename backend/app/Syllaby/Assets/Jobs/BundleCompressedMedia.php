<?php

namespace App\Syllaby\Assets\Jobs;

use Throwable;
use RuntimeException;
use ZipStream\ZipStream;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Assets\Media;
use App\System\Enums\QueueType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\FilesystemAdapter;
use App\Syllaby\Assets\Notifications\MediaBundleReady;

class BundleCompressedMedia implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected User $user, protected array $uuids)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::RENDER->value);
    }

    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        $media = Media::with('model')->whereIn('uuid', $this->uuids)
            ->where('user_id', $this->user->id)
            ->get();

        if ($media->isEmpty()) {
            return;
        }

        $timestamp = now()->timestamp;
        $filename = "{$this->user->name}-media-{$timestamp}.zip";

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('spaces');
        $disk->getClient()->registerStreamWrapper();

        $spaces = Arr::get($disk->getConfig(), 'bucket');
        $bucket = "tmp/zips/{$timestamp}/{$filename}";

        try {
            if (! $stream = fopen("s3://{$spaces}/{$bucket}", 'w')) {
                throw new RuntimeException("Could not open stream to {$bucket}");
            }

            $zip = new ZipStream(
                outputStream: $stream,
                enableZip64: false,
                sendHttpHeaders: false,
                outputName: $filename,
                flushOutput: true
            );

            foreach ($media as $item) {
                $path = $item->getPathRelativeToRoot();
                if ($file = $disk->readStream($path)) {
                    $zip->addFileFromStream($item->getDownloadFilename(), $file);
                    fclose($file);
                }
            }

            $zip->finish();
            fclose($stream);

            $url = $disk->temporaryUrl($bucket, now()->addHours(24), [
                'ResponseContentType' => 'application/zip',
                'ResponseContentDisposition' => "attachment; filename={$filename}",
            ]);

            $this->user->notify(new MediaBundleReady($url));

        } catch (Throwable $exception) {
            if (isset($stream) && is_resource($stream)) {
                fclose($stream);
            }

            Log::error('Failed to create zip bundle', ['message' => $exception->getMessage()]);
            throw $exception;
        }
    }
}
