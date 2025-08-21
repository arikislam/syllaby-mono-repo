<?php

namespace App\Syllaby\Videos\Jobs\Faceless;

use Exception;
use Throwable;
use Illuminate\Support\Arr;
use Illuminate\Bus\Batchable;
use App\System\Enums\QueueType;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use App\Syllaby\Generators\DTOs\ChatConfig;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Generators\DTOs\ChatResponse;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Generators\Vendors\Assistants\Chat;
use App\Syllaby\Generators\Prompts\GenreImagePrompt;
use App\Syllaby\Videos\Events\FacelessGenerationFailed;
use App\System\Jobs\Middleware\SwitchesManagersDrivers;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;

class TriggerMediaGeneration implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Queueable;

    const int CHUNK_COUNT = 12;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Faceless $faceless)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::FACELESS->value);
    }

    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        try {
            $segments = Arr::pluck($this->faceless->captions->content, 'text');
            $descriptions = $this->buildImagePrompts($segments);

            $jobs = collect($descriptions)->map(function ($description, $index) {
                return new GenerateFacelessMedia($this->faceless, $description, $index);
            })->toArray();

            $batch = Bus::batch($jobs)->name("Faceless Media Generation:{$this->faceless->id}")
                ->onQueue(QueueType::FACELESS->value)
                ->onConnection('videos')
                ->allowFailures()
                ->dispatch();

            $this->faceless->update(['batch' => $batch->id]);
        } catch (Exception $exception) {
            Log::error('Faceless Media Generation', [
                'error' => $exception->getMessage(),
                'faceless_id' => $this->faceless->id,
                'video_id' => $this->faceless->video_id,
            ]);

            throw $exception;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(): void
    {
        event(new FacelessGenerationFailed($this->faceless));
    }

    /**
     * Determine number of times the job may be attempted.
     */
    public function tries(): int
    {
        return count(Chat::getFacadeRoot()->getAvailableDrivers()) * 5;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->faceless->id;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ["faceless-media-trigger:{$this->faceless->id}"];
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            (new ThrottlesExceptionsWithRedis(5, 2))->backoff(2),
            (new SwitchesManagersDrivers(5))->using(Chat::getFacadeRoot())->by('chat-attempts'),
        ];
    }

    /**
     * Attempts to build a list of prompts based on a script to generate images.
     *
     * @throws Exception
     */
    private function buildImagePrompts(array $segments): array
    {
        $data = Arr::map($segments, fn ($segment) => [
            'excerpt' => $segment, 'image' => null,
        ]);

        $prompts = [];
        $genre = $this->faceless->genre;

        $chat = Chat::driver('claude');
        $config = $this->config();

        foreach (array_chunk($data, static::CHUNK_COUNT) as $chunk) {
            /** @var ChatResponse $response */
            $response = $chat->send(GenreImagePrompt::build($chunk, $genre, $this->faceless->character), $config);

            if (! $response->text || ! json_validate($response->text)) {
                throw new Exception('Unable to generate image prompts from script');
            }

            $output = Arr::get(json_decode($response->text, true), 'output');
            $prompts = [...$prompts, ...$output];
        }

        if (count($prompts) !== count($segments)) {
            throw new Exception('Prompt count mismatch');
        }

        return Arr::pluck($prompts, 'image');
    }

    /**
     * Drivers can switch dynamically on failure, therefore config is dynamic
     */
    private function config(): ChatConfig
    {
        [$base, $increment, $max] = [0.2, 0.1, 0.5];
        $temperature = min($base + ($this->attempts() * $increment), $max);

        return match (Chat::getFacadeRoot()->getCurrentDriver()) {
            'gpt' => new ChatConfig(
                model: 'o3-mini-2025-01-31',
                temperature: $temperature,
                responseFormat: config('openai.json_schemas.genre-images'),
            ),
            'claude' => new ChatConfig(temperature: $temperature),
            default => null,
        };
    }
}
