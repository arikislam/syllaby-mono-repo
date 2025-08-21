<?php

namespace App\Syllaby\Publisher\Metrics\Commands;

use Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use Illuminate\Database\Query\Builder;
use App\Syllaby\Publisher\Metrics\Jobs\ProcessMetricsChunkJob;

class ComputeMetricsAggregate extends Command
{
    protected $signature = 'metrics:compute-aggregate';

    protected $description = 'Aggregate publication metrics and store in publication_aggregates table';

    private const int CHUNK_SIZE = 500;

    public function handle(): void
    {
        $batches = [];

        $this->getGroupedMetrics()->chunkById(self::CHUNK_SIZE, function ($chunk) use (&$batches) {
            $batches[] = new ProcessMetricsChunkJob($chunk->pluck('id')->toArray());
        }, 'publication_id');

        if (empty($batches)) {
            return;
        }

        Bus::batch($batches)
            ->name(sprintf('aggregate-publication-metrics:%s', now()->toDateTimeString()))
            ->allowFailures()
            ->onQueue('default')
            ->catch(fn ($throwable) => Log::error("Batching failed: {$throwable->getMessage()}"))
            ->dispatch();
    }

    private function getGroupedMetrics(): Builder
    {
        return DB::table('publication_metric_values as pmv')
            ->join('publication_metric_keys as pmk', 'pmv.publication_metric_key_id', '=', 'pmk.id')
            ->select(['pmv.publication_id', 'pmv.social_channel_id', 'pmk.slug', DB::raw('MAX(pmv.id) as id')])
            ->groupBy('pmv.publication_id', 'pmv.social_channel_id', 'pmk.slug');
    }
}
