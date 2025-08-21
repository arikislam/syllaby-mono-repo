<?php

namespace App\Syllaby\Videos\Jobs\Faceless;

use Exception;
use Illuminate\Support\Arr;
use Laravel\Pennant\Feature;
use Illuminate\Bus\Batchable;
use App\System\Enums\QueueType;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Metadata\Timeline;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Syllaby\Videos\Enums\FacelessType;
use Illuminate\Foundation\Queue\Queueable;
use App\Syllaby\Videos\Enums\VideoProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Videos\Vendors\Remotion\Remotion;
use App\Syllaby\Videos\Events\FacelessGenerationFailed;
use App\Syllaby\Videos\Vendors\Faceless\Builder\AiVideo;
use App\Syllaby\Videos\Vendors\Faceless\Builder\BrollVideo;
use App\Syllaby\Videos\Vendors\Faceless\Provider\Creatomate;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use App\Syllaby\Videos\Vendors\Faceless\Builder\UrlBasedVideo;
use App\Syllaby\Videos\Vendors\Faceless\Builder\SingleClipVideo;

class BuildFacelessVideoSource implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Queueable;

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
     */
    public function handle(): void
    {
        $timeline = $this->faceless->timeline()->updateOrCreate([], [
            'provider' => VideoProvider::CREATOMATE->value,
            'content' => $this->source(),
            'user_id' => $this->faceless->user_id,
        ]);

        try {
            $response = $this->render($timeline);
        } catch (Exception $exception) {
            Log::warning('Faceless video generation failed! Job will be released.', [
                'faceless_id' => $this->faceless->id,
                'error' => $exception->getMessage(),
            ]);

            Cache::lock('lock:remotion:fails', 5)->block(10, function () {
                $current = Cache::get('remotion:fails', 0);
                Cache::put('remotion:fails', $current + 1, now()->addMinutes(15));
            });

            throw $exception;
        }

        $this->faceless->video()->update([
            'url' => Arr::get($response, 'url'),
            'status' => Arr::get($response, 'status'),
            'provider' => Arr::get($response, 'provider'),
            'synced_at' => Arr::get($response, 'synced_at'),
            'provider_id' => Arr::get($response, 'provider_id'),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(): void
    {
        event(new FacelessGenerationFailed($this->faceless));
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
        return ["faceless-video-source:{$this->faceless->id}"];
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            (new ThrottlesExceptionsWithRedis(2, 1))->by('creatomate:render')->backoff(1),
        ];
    }

    /**
     * Render the faceless video source.
     *
     * @throws Exception
     */
    private function render(Timeline $timeline): array
    {
        $driver = Feature::for($this->faceless->user)->value('render_driver');

        if ($driver === 'remotion' && $this->faceless->estimated_duration >= 600) {
            $driver = 'creatomate';
        }

        if ($driver === 'remotion' && Cache::get('remotion:fails', 0) >= 3) {
            $driver = 'creatomate';
        }

        if ($driver === 'remotion' && in_array($this->faceless->options->transition, ['mixed', 'none'])) {
            $driver = 'creatomate';
        }

        $options = [
            'faceless_id' => $this->faceless->id,
            'type' => $this->faceless->type->value,
        ];

        $response = match ($driver) {
            'remotion' => app(Remotion::class)->render($timeline->content, $options),
            'creatomate' => app(Creatomate::class)->render($timeline->content, $options),
        };

        return [
            'synced_at' => null,
            'url' => Arr::get($response, 'url'),
            'status' => Arr::get($response, 'status'),
            'provider' => Arr::get($response, 'provider'),
            'provider_id' => Arr::get($response, 'provider_id'),
        ];
    }

    /**
     * Get the source for the faceless video.
     */
    private function source(): array
    {
        $captions = $this->faceless->captions()->first();

        $source = match ($this->faceless->type) {
            FacelessType::AI_VISUALS => new AiVideo($this->faceless, $captions->content),
            FacelessType::B_ROLL => new BrollVideo($this->faceless, $captions->content),
            FacelessType::SINGLE_CLIP => new SingleClipVideo($this->faceless, $captions->content),
            FacelessType::URL_BASED => new UrlBasedVideo($this->faceless, $captions->content),
        };

        return $source->build();
    }
}
