<?php

namespace App\Syllaby\Publisher\Metrics\Jobs;

use Throwable;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Publisher\Metrics\AggregateType;
use App\Syllaby\Publisher\Metrics\PublicationAggregate;
use App\Syllaby\Publisher\Metrics\PublicationMetricValue;

class ProcessMetricsChunkJob implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $metrics) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        $metrics = PublicationMetricValue::query()
            ->whereIn('id', $this->metrics)
            ->with('key:id,slug')
            ->get()
            ->keyBy('id');

        $records = [];

        foreach ($this->metrics as $id) {
            if (! isset($metrics[$id])) {
                continue;
            }

            $metric = $metrics[$id];

            $records[] = [
                'publication_id' => $metric->publication_id,
                'social_channel_id' => $metric->social_channel_id,
                'key' => $metric->key->slug,
                'value' => $metric->value,
                'type' => AggregateType::TOTAL->value,
                'last_updated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($records)) {
            DB::transaction(callback: function () use ($records) {
                PublicationAggregate::upsert($records, ['publication_id', 'social_channel_id', 'key'], ['value', 'type', 'last_updated_at', 'updated_at']);
            }, attempts: 30);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $throwable): void
    {
        Log::error('Failed to process metrics chunk', [
            'reason' => $throwable->getMessage(),
            'batch_id' => $this->batch() ? $this->batch()->id : null,
        ]);
    }
}
