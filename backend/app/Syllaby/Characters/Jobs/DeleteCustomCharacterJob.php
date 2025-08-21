<?php

namespace App\Syllaby\Characters\Jobs;

use Http;
use Exception;
use App\Syllaby\Characters\Character;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteCustomCharacterJob implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Character $character) {}

    public function handle(): void
    {
        if (! $this->deleteTrainedModel()) {
            $this->fail("Failed to delete trained model for character: {$this->character->uuid} - Model {syllaby-ai/{$this->character->uuid}}");
        }

        $this->character->delete();
    }

    protected function deleteTrainedModel(): bool
    {
        /**
         * Syntax for a model: {owner-name}/{model-name}:{version}
         *
         * For the sake of simplicity and uniqueness, we use the character's UUID as the model name.
         * And the owner name is always "syllaby-ai".
         */
        $version = explode(':', $this->character->model)[1] ?? null;

        if (is_null($version)) {
            return Http::replicate()->delete("models/syllaby-ai/{$this->character->uuid}")->successful();
        }

        $result = Http::replicate()->delete("/models/syllaby-ai/{$this->character->uuid}/versions/{$version}");

        if ($result->failed()) {
            throw new Exception("Failed to delete version {$version} for character {$this->character->uuid}: {$result->body()}");
        }

        return Http::replicate()->delete("models/syllaby-ai/{$this->character->uuid}")->successful();
    }
}
