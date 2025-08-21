<?php

namespace App\Syllaby\Videos\Jobs\Renders;

use Exception;
use Throwable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Videos\Video;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Videos\Enums\VideoProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateVideoRenderMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly Video $video, private readonly array $data)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::RENDER->value);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->download();
            $this->markAsComplete();
            $this->clean();
        } catch (Exception $exception) {
            $this->fail($exception);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Unable to create video {id} media record from {provider}', [
            'id' => $this->video->id,
            'error' => $exception->getMessage(),
            'provider' => $this->video->provider,
        ]);

        $this->video->update(['status' => VideoStatus::SYNC_FAILED]);
    }

    /**
     * Download and creates a media record for the rendered video.
     *
     * @throws Exception
     */
    private function download(): void
    {
        $url = Arr::get($this->data, 'render_url');

        $extension = File::extension($url);
        $name = Str::limit(Str::slug($this->video->title), 50, '');

        $this->video->addMediaFromUrl($url)
            ->setName($name)
            ->setFileName("{$name}.{$extension}")
            ->addCustomHeaders(['ACL' => 'public-read'])
            ->withAttributes(['user_id' => $this->video->user_id])
            ->toMediaCollection('video');
    }

    /**
     * Marks the video as completed, registering the synced at time.
     */
    private function markAsComplete(): Video
    {
        return tap($this->video)->update([
            'url' => null,
            'synced_at' => now(),
            'status' => VideoStatus::COMPLETED,
        ]);
    }

    /**
     * Cleans the video render media.
     */
    private function clean(): void
    {
        if ($this->video->provider === VideoProvider::REMOTION->value) {
            Storage::disk('s3')->deleteDirectory("renders/{$this->video->provider_id}");
        }
    }
}
