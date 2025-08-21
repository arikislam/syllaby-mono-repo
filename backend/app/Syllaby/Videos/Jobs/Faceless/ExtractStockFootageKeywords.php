<?php

namespace App\Syllaby\Videos\Jobs\Faceless;

use Exception;
use Illuminate\Support\Str;
use App\Syllaby\Assets\Asset;
use Illuminate\Bus\Batchable;
use App\System\Enums\QueueType;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Assets\Enums\AssetStatus;
use Illuminate\Foundation\Queue\Queueable;
use App\Syllaby\Assets\Enums\AssetProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Assets\DTOs\AssetCreationData;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Assets\Actions\CreateFacelessAssetAction;
use App\Syllaby\Generators\Vendors\Assistants\Chat;
use App\Syllaby\Generators\Prompts\StockSearchPrompt;
use App\Syllaby\Videos\Events\FacelessGenerationFailed;
use App\System\Jobs\Middleware\SwitchesManagersDrivers;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;

class ExtractStockFootageKeywords implements ShouldBeUnique, ShouldQueue
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
        $response = Chat::driver('claude')->send(
            StockSearchPrompt::build($this->faceless->script)
        );

        if (blank($response->text)) {
            throw new Exception('Unable to find stock footage keywords');
        }

        if ($this->hasAssets()) {
            $this->recover($response->text);
        } else {
            $this->fromScratch($response->text);
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
        return ["faceless-stock-keywords:{$this->faceless->id}"];
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
     * Fetch the assets.
     */
    private function hasAssets(): bool
    {
        return $this->faceless->assets()->where('active', true)->exists();
    }

    /**
     * Create assets from scratch.
     */
    private function fromScratch(string $keywords): void
    {
        $segments = count($this->faceless->captions->content);

        foreach (range(0, $segments - 1) as $index) {
            $data = $this->assetData($keywords, $index);
            app(CreateFacelessAssetAction::class)->handle($this->faceless, $data);
        }
    }

    /**
     * Recover assets.
     */
    private function recover(string $keywords): void
    {
        $start = $this->faceless->assets()
            ->where('type', AssetType::STOCK_VIDEO)
            ->where('active', true)
            ->count();

        $total = count($this->faceless->captions->content) - 1;

        foreach (range($start, $total) as $index) {
            $data = $this->assetData($keywords, $index);
            app(CreateFacelessAssetAction::class)->handle($this->faceless, $data);
        }
    }

    /**
     * Get the asset data.
     */
    protected function assetData(string $keywords, int $index): AssetCreationData
    {
        return new AssetCreationData(
            user: $this->faceless->user,
            provider: AssetProvider::PEXELS,
            provider_id: Str::uuid(),
            type: AssetType::fromMime('video/mp4'),
            genre: $this->faceless->genre,
            status: AssetStatus::PROCESSING,
            order: $index,
            isPrivate: true,
            description: $keywords,
            active: true,
        );
    }
}
