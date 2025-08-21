<?php

namespace App\Syllaby\Videos\Jobs\Faceless;

use Exception;
use Throwable;
use Illuminate\Support\Arr;
use Illuminate\Bus\Batchable;
use App\System\Enums\QueueType;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Characters\Genre;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Characters\Character;
use App\Syllaby\Videos\Enums\Dimension;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Assets\DTOs\AssetCreationData;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Assets\Actions\CreateFacelessAssetAction;
use App\Syllaby\Generators\Vendors\Images\Factory;
use App\Syllaby\Generators\DTOs\ImageGeneratorResponse;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;

class GenerateFacelessMedia implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Faceless $faceless, protected string $description, protected int $index)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::FACELESS->value);
    }

    /**
     * Execute the job.
     */
    public function handle(Factory $factory): void
    {
        /** @var Genre $genre */
        $genre = $this->faceless->genre;

        $aspectRatio = $this->faceless->options->aspect_ratio;

        $provider = $this->provider();

        $payload = match ($provider) {
            'syllaby' => $this->syllaby($genre, $this->description, $aspectRatio),
            'replicate' => $this->replicate($genre, $this->description, $aspectRatio),
        };

        $payload['context'] = [
            'model_id' => $this->faceless->id,
            'model_type' => $this->faceless->getMorphClass(),
        ];

        if (! $prediction = $factory->for('replicate')->async()->image($payload, $this->description)) {
            throw new Exception('Unable to start image generation');
        }

        $this->createAssetFrom($prediction);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("Faceless Media Generation Failed: {$this->faceless->id}", [
            'exception' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "{$this->faceless->id}:{$this->index}";
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ["faceless-media-generation:{$this->faceless->id}:{$this->index}"];
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
    private function provider(): string
    {
        return blank($this->faceless->character) ? 'replicate' : 'syllaby';
    }

    /**
     * Get the data for the replicate provider.
     */
    private function replicate(Genre $genre, string $description, string $aspectRatio): array
    {
        $data = $genre->build($description, Dimension::fromAspectRatio($aspectRatio));

        if (blank($genre->details)) {
            return $data;
        }

        Arr::set($data, 'input.prompt', $description);

        return $data;
    }

    /**
     * Get the data for the syllaby provider.
     */
    private function syllaby(Genre $genre, string $description, string $aspectRatio): array
    {
        /** @var Character $character */
        $character = $this->faceless->character;

        $data = $genre->build("{$character->trigger} {$description}", Dimension::fromAspectRatio($aspectRatio));

        if ($character->description) {
            Arr::set($data, 'input.prompt', $description);
        }

        return array_merge($data, ['model' => $character->model]);
    }

    /**
     * Downloads the generated image and adds it to faceless genre media collection.
     */
    private function createAssetFrom(ImageGeneratorResponse $prediction): void
    {
        $data = AssetCreationData::forAiImage($this->faceless, $prediction, $this->index, true);

        app(CreateFacelessAssetAction::class)->handle($this->faceless, $data);
    }
}
