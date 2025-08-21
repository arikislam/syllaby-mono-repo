<?php

namespace App\Syllaby\Videos\Jobs\Faceless;

use Closure;
use Exception;
use Throwable;
use Illuminate\Support\Arr;
use App\Syllaby\Assets\Asset;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Sleep;
use App\System\Enums\QueueType;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Videos\Enums\Dimension;
use App\Syllaby\Assets\Enums\AssetStatus;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Assets\Actions\TransloadMediaAction;
use App\Syllaby\Assets\Contracts\StockVideoContract;
use App\Syllaby\Videos\Events\FacelessGenerationFailed;

class FindStockFootageClips implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Queueable;

    private array $data = [
        'page' => 1,
        'per_page' => 80,
        'size' => 'large',
        'orientation' => 'portrait',
    ];

    private array $clips = [];

    private StockVideoContract $library;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Faceless $faceless)
    {
        $this->library = app(StockVideoContract::class);

        $this->onConnection('videos');
        $this->onQueue(QueueType::FACELESS->value);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $assets = $this->faceless->assets()
            ->whereIn('status', [AssetStatus::PROCESSING, AssetStatus::FAILED])
            ->where('type', AssetType::STOCK_VIDEO)
            ->where('active', true)
            ->orderBy('id', 'asc')
            ->get();

        $dimension = Dimension::fromAspectRatio($this->faceless->options->aspect_ratio);
        $keywords = $assets->first()->description;

        $results = $this->search($keywords, $dimension, $assets->count());

        if (count($results) < $assets->count()) {
            $this->fail('No stock footage clips found');

            return;
        }

        $assets->each(function (Asset $asset) use ($results) {
            $status = $this->download($results[$asset->pivot->order], $asset);
            $asset->update(['status' => $status]);

            if ($status->is(AssetStatus::FAILED)) {
                throw new Exception('Failed to download stock footage clip');
            }
        });
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
        return ["faceless-stock-clips:{$this->faceless->id}"];
    }

    /**
     * Search for a stock footage clip.
     */
    private function search(string $keywords, Dimension $dimension, int $total, int $attempts = 6): array
    {
        if ($attempts === 0) {
            return [];
        }

        Sleep::for(1)->seconds();

        $this->data['query'] = $keywords;
        $this->data['orientation'] = $dimension->value;

        if (! ($results = $this->library->search($this->data)) || $this->data['page'] > 20) {
            return [];
        }

        $results = collect($results)->filter($this->filterClips($dimension))->pluck('url');

        if ($results->isEmpty()) {
            $this->data['page']++;

            return $this->search($keywords, $dimension, $total, $attempts);
        }

        $remaining = $total - count($this->clips);
        $this->clips = array_merge($this->clips, array_slice($results->toArray(), 0, $remaining));

        if (count($this->clips) < $total) {
            $this->data['page']++;

            return $this->search($keywords, $dimension, $total, $attempts);
        }

        $this->data['page'] = 1;

        return $this->clips;
    }

    /**
     * Filter the stock footage clips.
     */
    private function filterClips(Dimension $dimension): Closure
    {
        return function ($video) use ($dimension) {
            $width = Arr::get($video, 'width') >= $dimension->get('width');
            $height = Arr::get($video, 'height') >= $dimension->get('height');
            $duration = Arr::get($video, 'duration') >= 5 && Arr::get($video, 'duration') <= 78;

            return $duration && $width && $height;
        };
    }

    /**
     * Download the stock footage clip.
     */
    private function download(string $url, Asset $asset): AssetStatus
    {
        try {
            app(TransloadMediaAction::class)->handle($asset, $url);
        } catch (Throwable) {
            return AssetStatus::FAILED;
        }

        return AssetStatus::SUCCESS;
    }
}
