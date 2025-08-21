<?php

namespace App\Syllaby\Videos\Jobs\Faceless;

use Throwable;
use Illuminate\Bus\Batchable;
use App\System\Enums\QueueType;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Assets\Enums\AssetStatus;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Videos\Events\FacelessGenerationFailed;
use App\Syllaby\Assets\Strategies\AssetRepetitionStrategyFactory;

class ManageFacelessAssets implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Faceless $faceless, protected array $selected = [])
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::FACELESS->value);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! empty($this->selected)) {
            $this->storeSelectedAssets();
        }

        $this->handleAssetRepetition();
    }

    /**
     * Store the selected assets in the database
     */
    protected function storeSelectedAssets(): void
    {
        $sorted = collect($this->selected)->sortBy('order')->values();

        $ids = $sorted->pluck('id')->toArray();

        DB::transaction(function () use ($ids) {
            $this->faceless->assets()->detach();

            foreach ($ids as $index => $asset) {
                $this->faceless->assets()->attach($asset, ['order' => $index, 'active' => true]);
            }
        });
    }

    /**
     * Handle asset repetition if needed
     */
    protected function handleAssetRepetition(): void
    {
        $this->faceless->load('captions');

        if (! $this->faceless->captions || empty($this->faceless->captions->content)) {
            return;
        }

        $captions = count($this->faceless->captions->content);

        $assets = $this->faceless->assets()
            ->where('active', true)
            ->where('status', AssetStatus::SUCCESS)
            ->oldest('order')
            ->get();

        if ($assets->isEmpty()) {
            return;
        }

        if ($assets->count() < $captions) {
            $this->storeRepeatedAssets($assets, $captions);
        }
    }

    /**
     * Store repeated assets in the database
     */
    protected function storeRepeatedAssets(Collection $assets, int $targetCount): void
    {
        $strategy = AssetRepetitionStrategyFactory::make();

        $repeated = $strategy->repeat($assets, $targetCount);

        DB::transaction(function () use ($repeated) {
            $this->faceless->assets()->detach();

            foreach ($repeated as $index => $asset) {
                $this->faceless->assets()->attach($asset['id'], ['order' => $index, 'active' => true]);
            }
        });
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Faceless [{id}] - Asset management failed', [
            'id' => $this->faceless->id,
            'error' => $exception->getMessage(),
        ]);

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
        return ["scrapped-faceless-assets:{$this->faceless->id}"];
    }
}
