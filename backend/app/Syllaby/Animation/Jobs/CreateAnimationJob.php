<?php

namespace App\Syllaby\Animation\Jobs;

use Exception;
use App\Syllaby\Assets\Asset;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Syllaby\Assets\Enums\AssetStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Shared\RateLimiter\SlidingWindowLimiter;
use App\Syllaby\Animation\Contracts\AnimationGenerator;
use App\Syllaby\Animation\Events\AnimationGenerationFailed;

class CreateAnimationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const string MINIMAX_CACHE = 'animation-minimax-attempts';

    const string GLOBAL_KEY = 'global';

    public int $tries = 20;

    public function __construct(public Asset $asset, public Faceless $faceless, public string $mediaUrl, public ?string $prompt = null)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::FACELESS->value);
    }

    public function handle(AnimationGenerator $animation): void
    {
        $limiter = SlidingWindowLimiter::for(
            name: self::MINIMAX_CACHE,
            maxAttempts: config('services.minimax.rate_limit_attempts', 100),
            decaySeconds: 60
        );

        if (! $limiter->attempt(self::GLOBAL_KEY)) {
            $seconds = $limiter->availableIn(self::GLOBAL_KEY);
            $delay = ($this->attempts() ** 2) + $seconds + random_int(1, 10);
            Log::alert("Minimax Rate Limit Exceeded. Retrying in {$delay} seconds.");
            $this->release($delay);

            return;
        }

        $response = $animation->generate($this->faceless, $this->mediaUrl, $this->prompt);

        $this->asset->update([
            'provider_id' => $response->id,
            'provider' => $response->provider,
            'description' => $response->description,
            'model' => $response->model,
            'status' => AssetStatus::PROCESSING,
        ]);

        if (config('services.animation.use_polling')) {
            PollAnimationGeneration::dispatch($this->asset, $this->faceless);
        }
    }

    public function failed(Exception $exception): void
    {
        Log::alert("Asset {$this->asset->id} animation failed", [
            'error' => $exception->getMessage(),
        ]);

        event(new AnimationGenerationFailed($this->asset, $this->faceless));
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'animation-generation',
            sprintf('faceless:%s', $this->faceless->id),
            sprintf('asset:%s', $this->asset->id),
        ];
    }
}
