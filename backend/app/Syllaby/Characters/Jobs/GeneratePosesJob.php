<?php

namespace App\Syllaby\Characters\Jobs;

use Log;
use Exception;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Characters\Character;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Characters\Enums\CharacterStatus;
use App\Syllaby\Characters\Events\CharacterGenerationFailed;

class GeneratePosesJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public Character $character) {}

    public function handle(): void
    {
        $preview = $this->character->getFirstMediaUrl('preview');

        if (empty($preview)) {
            throw new Exception("Prview not found for character - {$this->character->id}.");
        }

        $response = Http::replicate()->post('/models/flux-kontext-apps/portrait-series/predictions', [
            'input' => [
                'input_image' => $preview,
                'background' => 'white',
                'randomize_images' => true,
                'num_images' => 12,
            ],
            'webhook_events_filter' => config('services.replicate.webhook.filters'),
            'webhook' => route('custom-character.webhook', ['type' => 'poses', 'character' => $this->character->id]),
        ]);

        if ($response->failed()) {
            throw new Exception("Error from Replicate API during pose generation: {$response->body()}");
        }

        $identifier = $response->json('id') ?? throw new Exception("Prediction ID not found in Replicate response for pose generation: {$response->body()}");

        $this->character->update(['status' => CharacterStatus::POSE_GENERATING, 'provider_id' => $identifier]);
    }

    public function failed(Exception $exception): void
    {
        $this->character->update(['status' => CharacterStatus::POSE_FAILED]);

        event(new CharacterGenerationFailed($this->character));

        Log::error('Custom Character Pose Generation job failed', [
            'character_id' => $this->character->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
