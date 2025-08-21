<?php

namespace App\Syllaby\Characters\Jobs;

use Exception;
use RuntimeException;
use ZipStream\ZipStream;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Characters\Character;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Characters\Enums\CharacterStatus;
use App\Syllaby\Characters\Events\CharacterGenerationFailed;

class TriggerLoraTraining implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Character $character) {}

    public function handle(): void
    {
        [$owner, $name] = $this->createReplicateModel();

        $file = $this->createZipFile();

        $response = Http::replicate()->post('/models/ostris/flux-dev-lora-trainer/versions/4ffd32160efd92e956d39c5338a9b8fbafca58e03f791f6d8011f3e20e8ea6fa/trainings', [
            'destination' => "{$owner}/{$name}",
            'input' => [
                'steps' => 1000,
                'lora_rank' => 32,
                'input_images' => $file,
                'trigger_word' => $this->character->uuid,
            ],
            'webhook_events_filter' => config('services.replicate.webhook.filters'),
            'webhook' => route('custom-character.webhook', ['type' => 'final', 'character' => $this->character->id]),
        ]);

        if ($response->failed()) {
            throw new Exception("Error from Replicate API during Lora training: {$response->body()}");
        }

        $this->character->update([
            'status' => CharacterStatus::MODEL_TRAINING,
            'provider_id' => $response->json('id'),
            'training_images' => $this->character->getMedia('poses')->count(),
        ]);
    }

    private function createReplicateModel(): array
    {
        $response = Http::replicate()->post('/models', [
            'owner' => 'syllaby-ai',
            'name' => $this->character->uuid,
            'visibility' => 'private',
            'hardware' => 'gpu-h100',
        ]);

        if ($this->modelExists($response)) {
            return ['syllaby-ai', $this->character->uuid];
        }

        if ($response->failed()) {
            throw new Exception("Failed to create Replicate model: {$response->body()}");
        }

        return [$response->json('owner'), $response->json('name')];
    }

    protected function createZipFile(): string
    {
        $media = $this->character->getMedia('poses');

        if ($media->isEmpty()) {
            throw new Exception("No poses found for character - {$this->character->id}.");
        }

        $timestamp = now()->timestamp;
        $filename = sprintf('%s-%s.zip', $this->character->uuid, $timestamp);

        $disk = Storage::disk('spaces');
        $disk->getClient()->registerStreamWrapper();

        $base = "tmp/zips/{$timestamp}/{$filename}";

        $spaces = Arr::get($disk->getConfig(), 'bucket');
        $bucket = $disk->path($base);

        $context = stream_context_create([
            's3' => [
                'ACL' => 'public-read',
            ],
        ]);

        if (! $stream = fopen("s3://{$spaces}/{$bucket}", 'w', false, $context)) {
            throw new RuntimeException("Could not open stream to {$bucket}");
        }

        $zip = new ZipStream(outputStream: $stream, enableZip64: false, sendHttpHeaders: false, outputName: $filename, flushOutput: true);

        foreach ($media as $item) {
            $path = $item->getPathRelativeToRoot();
            if ($file = $disk->readStream($path)) {
                $zip->addFileFromStream($item->getDownloadFilename(), $file);
                fclose($file);
            }
        }

        $zip->finish();
        fclose($stream);

        return $disk->url($base);
    }

    private function modelExists(PromiseInterface|Response $response): bool
    {
        $target = 'a model with that name and owner already exists.';

        $message = $response->json('errors.0.detail');

        return $response->conflict() && is_string($message) && str_contains(strtolower($message), strtolower($target));
    }

    public function failed(Exception $exception): void
    {
        $this->character->update(['status' => CharacterStatus::MODEL_TRAINING_FAILED]);

        event(new CharacterGenerationFailed($this->character));

        Log::error('Custom Character Lora Training job failed', [
            'character_id' => $this->character->id,
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
