<?php

namespace App\Syllaby\Schedulers\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Bus\Batchable;
use App\System\Enums\QueueType;
use App\Syllaby\Generators\Generator;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Credits\CreditHistory;
use App\Syllaby\Schedulers\Occurrence;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Generators\DTOs\ChatResponse;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Generators\Actions\WriteScriptAction;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;

class GenerateOccurrenceScript implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Scheduler $scheduler, protected Carbon $date)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::RENDER->value);
    }

    /**
     * Execute the job.
     */
    public function handle(WriteScriptAction $writer): void
    {
        $occurrence = $this->scheduler->occurrences()
            ->where('occurs_at', $this->date)
            ->first();

        $generator = $occurrence->generator;

        if (! $response = $writer->handle($generator)) {
            $this->release(10);

            return;
        }

        $occurrence->update([
            'script' => $response->text,
            'status' => 'completed',
        ]);

        $this->charge($occurrence, $generator, $response);
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    /**
     * Charge the user for the script generation.
     */
    private function charge(Occurrence $occurrence, Generator $generator, ChatResponse $response): void
    {
        (new CreditService($this->scheduler->user))->decrement(
            type: CreditEventEnum::CONTENT_PROMPT_REQUESTED,
            creditable: $generator,
            amount: $response->completionTokens,
            meta: ['content_type' => $occurrence->getMorphClass()],
            label: Str::limit($response->text, CreditHistory::TRUNCATED_LENGTH)
        );
    }
}
