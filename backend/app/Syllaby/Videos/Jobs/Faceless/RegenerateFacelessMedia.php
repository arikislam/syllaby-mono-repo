<?php

namespace App\Syllaby\Videos\Jobs\Faceless;

use Exception;
use Throwable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Assets\Asset;
use App\System\Enums\QueueType;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Characters\Genre;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Characters\Character;
use App\Syllaby\Videos\Enums\Dimension;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Generators\Vendors\Images\Factory;
use App\Syllaby\Generators\Vendors\Assistants\Chat;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;

class RegenerateFacelessMedia implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Asset $asset, protected int $id, protected bool $flagged = false)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::FACELESS->value);
    }

    /**
     * Execute the job.
     */
    public function handle(Factory $factory): void
    {
        if ($this->asset->retries > 3) {
            return;
        }

        if (! $faceless = Faceless::find($this->id)) {
            return;
        }

        if ($this->flagged) {
            $this->asset->description = $this->rephrase();
        }

        $genre = $this->asset->genre;

        $orientation = Dimension::from($this->asset->orientation);

        $provider = $this->provider($faceless);

        $payload = match ($provider) {
            'syllaby' => $this->syllaby($faceless, $this->asset->description, $orientation),
            'replicate' => $this->replicate($genre, $this->asset->description, $orientation),
        };

        $payload['context'] = [
            'model_id' => $faceless->id,
            'model_type' => $faceless->getMorphClass(),
        ];

        if (! $prediction = $factory->for('replicate')->async()->image($payload, $this->asset->description)) {
            $this->asset->save();
            throw new Exception('Unable to start image generation');
        }

        $this->asset->retries++;
        $this->asset->provider_id = $prediction->id;

        $this->asset->save();
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Failed to regenerate faceless media', [
            'asset_id' => $this->asset->id,
            'exception' => $exception->getMessage(),
        ]);
    }

    public function uniqueId(): string
    {
        return $this->asset->id;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ["faceless-media-regeneration:{$this->asset->id}"];
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            (new ThrottlesExceptionsWithRedis(3, 1))->backoff(1),
        ];
    }

    /**
     * Get the provider for the faceless media generation.
     */
    private function provider(Faceless $faceless): string
    {
        return blank($faceless->character) ? 'replicate' : 'syllaby';
    }

    /**
     * Get the data for the replicate provider.
     */
    private function replicate(Genre $genre, string $description, Dimension $orientation): array
    {
        $data = $genre->build($description, $orientation);

        if (blank($genre->details)) {
            return $data;
        }

        Arr::set($data, 'input.prompt', $description);

        return $data;
    }

    /**
     * Get the data for the syllaby provider.
     */
    private function syllaby(Faceless $faceless, string $description, Dimension $orientation): array
    {
        /** @var Character $character */
        $character = $faceless->character;

        $data = $faceless->genre->build($description, $orientation);

        if ($character->description) {
            Arr::set($data, 'input.prompt', $description);
        }

        return array_merge($data, ['model' => $character->model]);
    }

    /**
     * Rephrase the description.
     */
    private function rephrase(): string
    {
        $prompt = <<<'PROMPT'
            The following description was flagged as inappropriate. Either NSFW, violent, sexual or offensive.
            Please rewrite it to be more friendly and appropriate for all audiences making sure to keep the original meaning.
            Description: ":DESCRIPTION"

            Output just a simple string with the rewritten description, nothing else.
        PROMPT;

        $prompt = Str::replace(':DESCRIPTION', $this->asset->description, $prompt);

        if (! $response = Chat::driver('claude')->send($prompt)) {
            return $this->asset->description;
        }

        return $response->text;
    }
}
