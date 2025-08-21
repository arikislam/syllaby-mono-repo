<?php

namespace App\Syllaby\Generators\Jobs;

use Exception;
use Illuminate\Support\Arr;
use App\Syllaby\Assets\Asset;
use App\System\Enums\QueueType;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Syllaby\Credits\CreditHistory;
use App\Syllaby\Assets\Enums\AssetStatus;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Videos\Contracts\ImageModerator;
use App\Syllaby\Assets\Actions\TransloadMediaAction;
use App\Syllaby\Generators\DTOs\ImageGeneratorResponse;
use App\Syllaby\Videos\Events\FacelessGenerationFailed;
use App\Syllaby\Videos\Jobs\Faceless\StoreFlaggedMedia;
use App\Syllaby\Videos\Jobs\Faceless\RegenerateFacelessMedia;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use App\Syllaby\Videos\Jobs\Faceless\BuildFacelessVideoSource;

class ProcessIncomingPrediction implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected ImageGeneratorResponse $input, protected array $context)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::FACELESS->value);
    }

    /**
     * Execute the job.
     */
    public function handle(ImageModerator $moderator): void
    {
        if (! $asset = $this->fetchAsset()) {
            return;
        }

        if ($this->status()->is(AssetStatus::FAILED)) {
            $this->regenerate($asset, 'Prediction failed');

            return;
        }

        if (! $this->download($this->input->url, $asset)) {
            throw new Exception('Unable to download image from prediction');
        }

        if ($moderator->inspect($this->input->url)->isNSFW()) {
            $this->regenerate($asset, 'Flagged image as NSFW by Gemini', true, $this->input->url);

            return;
        }

        DB::transaction(function () use ($asset) {
            $asset = $this->lockAsset($asset->id);

            if ($asset->status->is(AssetStatus::SUCCESS)) {
                return null;
            }

            $asset->update(['status' => AssetStatus::SUCCESS]);
        }, 4);

        Cache::lock($this->getLockKey(), 30)->block(4, function () {
            DB::transaction(fn () => $this->process(), 4);
        });
    }

    /**
     * Handle a job failure.
     */
    public function failed(): void
    {
        if (! $asset = $this->fetchAsset()) {
            return;
        }

        if ($asset->retries < 3) {
            return;
        }

        Log::warning('Marking asset as failed after attempting {retries} retries', [
            'retries' => $asset->retries,
            'asset' => $asset->id,
        ]);

        $faceless = DB::transaction(function () use ($asset) {
            $asset = tap($this->lockAsset($asset->id))->update([
                'status' => AssetStatus::FAILED,
            ]);

            return tap($this->lockFaceless(), function (Faceless $faceless) use ($asset) {
                $faceless->assets()->where('id', $asset->id)->update(['active' => false]);
            });
        });

        if (! $this->refunded($faceless)) {
            event(new FacelessGenerationFailed($faceless));
        }
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->input->id;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [sprintf('%s-prediction:%s', $this->input->provider, $this->input->id)];
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
     * Fetches the asset from the database.
     */
    private function fetchAsset(): ?Asset
    {
        return Asset::where('provider_id', $this->input->id)
            ->where('provider', $this->input->provider)
            ->first();
    }

    /**
     * Locks the asset for the job.
     */
    private function lockAsset(int $id): ?Asset
    {
        return Asset::sharedLock()->find($id);
    }

    /**
     * Locks the faceless for the job.
     */
    private function lockFaceless(): ?Faceless
    {
        return Faceless::sharedLock()->find(
            Arr::get($this->context, 'model_id')
        );
    }

    /**
     * Processes the completion of the faceless assets if ready.
     */
    private function process(): void
    {
        $faceless = $this->lockFaceless();

        if (! $faceless || $this->hasIncompleteAssets($faceless)) {
            return;
        }

        $this->handleAssetsCompletion($faceless);
    }

    /**
     * Maps the status from the prediction to the asset status.
     */
    private function status(): AssetStatus
    {
        return match ($this->input->status) {
            'starting', 'processing', 'queued' => AssetStatus::PROCESSING,
            'failed', 'canceled' => AssetStatus::FAILED,
            default => AssetStatus::SUCCESS,
        };
    }

    /**
     * Checks if there are any incomplete assets for the faceless.
     */
    private function hasIncompleteAssets(Faceless $faceless): bool
    {
        return DB::table('video_assets')->select('assets.id')
            ->join('assets', 'assets.id', '=', 'video_assets.asset_id')
            ->where('assets.status', '!=', AssetStatus::SUCCESS->value)
            ->where('video_assets.model_type', $faceless->getMorphClass())
            ->where('video_assets.model_id', $faceless->id)
            ->where('video_assets.active', true)
            ->limit(1)
            ->exists();
    }

    /**
     * Regenerates the faceless media.
     */
    private function regenerate(Asset $asset, string $reason, bool $flagged = false, ?string $url = null): void
    {
        Log::error($reason, ['asset' => $asset->id, 'url' => $url]);

        dispatch(new StoreFlaggedMedia($url));

        $id = Arr::get($this->context, 'model_id');

        dispatch(new RegenerateFacelessMedia($asset, $id, $flagged));
    }

    /**
     * Downloads the image from the prediction.
     */
    private function download(string $url, Asset $asset): bool
    {
        try {
            app(TransloadMediaAction::class)->handle($asset, $url, order: 0);
        } catch (Exception) {
            return false;
        }

        return true;
    }

    /**
     * Handles the completion of the faceless assets.
     */
    private function handleAssetsCompletion(Faceless $faceless): void
    {
        dispatch(new BuildFacelessVideoSource($faceless))->afterCommit();
    }

    /**
     * Checks if the faceless has been refunded from the last generation.
     */
    private function refunded(Faceless $faceless): bool
    {
        $transaction = CreditHistory::query()->where('user_id', $faceless->user_id)
            ->where('creditable_type', $faceless->getMorphClass())
            ->where('creditable_id', $faceless->id);

        $refunds = $transaction->where('description', CreditEventEnum::REFUNDED_CREDITS->value)->count();
        $generations = $transaction->where('description', CreditEventEnum::FACELESS_VIDEO_GENERATED->value)->count();

        return $refunds >= $generations;
    }

    /**
     * Gets the lock key for the job.
     */
    private function getLockKey(): string
    {
        $id = Arr::get($this->context, 'model_id');

        return "processing-predictions:faceless:{$id}";
    }
}
