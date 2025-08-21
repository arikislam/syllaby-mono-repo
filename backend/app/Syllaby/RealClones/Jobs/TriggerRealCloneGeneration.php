<?php

namespace App\Syllaby\RealClones\Jobs;

use Exception;
use Throwable;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use App\Syllaby\Speeches\Speech;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Syllaby\RealClones\RealClone;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Syllaby\RealClones\Vendors\Presenter;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\RealClones\Enums\RealCloneStatus;

class TriggerRealCloneGeneration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public RealClone $clone;

    public Speech $speech;

    /**
     * Create a new job instance.
     */
    public function __construct(RealClone $clone, Speech $speech)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::RENDER->value);

        $this->clone = $clone->withoutRelations();
        $this->speech = $speech->withoutRelations();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Presenter::driver($this->clone->provider)->generate($this->clone, $this->speech);
        } catch (Exception $exception) {
            $this->fail($exception);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Unable to start generating the real clone {id} with {provider}', [
            'id' => $this->clone->id,
            'error' => $exception->getMessage(),
            'provider' => $this->clone->provider,
        ]);

        DB::transaction(function () {
            $this->refund();
            $this->clone->update(['status' => RealCloneStatus::FAILED, 'synced_at' => null]);
        });
    }

    /**
     * Refund the user's credits for the failed real clone.
     */
    private function refund(): void
    {
        (new CreditService($this->clone->user))->refund($this->clone);
    }
}
