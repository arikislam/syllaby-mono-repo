<?php

namespace App\Syllaby\Credits\Jobs;

use Exception;
use Illuminate\Bus\Batchable;
use App\System\Enums\QueueType;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Videos\Events\FacelessGenerationFailed;
use App\Syllaby\Credits\Actions\ChargeFacelessVideoAction;

class ProcessFacelessVideoCharge implements ShouldQueue
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
    public function handle(ChargeFacelessVideoAction $charge): void
    {
        try {
            $charge->handle($this->faceless, $this->faceless->user);
        } catch (Exception $exception) {
            Log::error('Faceless [{id}] - Charge failed', [
                'id' => $this->faceless->id,
                'error' => $exception->getMessage(),
            ]);

            $this->fail($exception);
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
        return ["faceless-charge:{$this->faceless->id}"];
    }
}
