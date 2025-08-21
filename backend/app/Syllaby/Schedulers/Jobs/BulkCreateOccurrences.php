<?php

namespace App\Syllaby\Schedulers\Jobs;

use Carbon\Carbon;
use App\System\Enums\QueueType;
use App\Syllaby\Generators\Generator;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\Occurrence;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class BulkCreateOccurrences implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Scheduler $scheduler, protected array $dates)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::RENDER->value);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $generator = $this->scheduler->generator;

        foreach ($generator->context as $index => $title) {
            $this->generator(
                occurrence: $this->create($title, $index),
                reference: $generator
            );
        }
    }

    /**
     * Create an occurrence.
     */
    private function create(string $title, int $index): Occurrence
    {
        return $this->scheduler->occurrences()->create([
            'topic' => $title,
            'status' => 'generating',
            'user_id' => $this->scheduler->user_id,
            'occurs_at' => Carbon::parse($this->dates[$index]),
        ]);
    }

    /**
     * Create a generator for the occurrence.
     */
    private function generator(Occurrence $occurrence, Generator $reference): void
    {
        $occurrence->generator()->create([
            'topic' => $occurrence->topic,
            'tone' => $reference->tone,
            'length' => $reference->length,
            'style' => $reference->style,
            'language' => $reference->language,
        ]);
    }
}
