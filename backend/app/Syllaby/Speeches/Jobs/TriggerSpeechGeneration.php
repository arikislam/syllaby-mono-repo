<?php

namespace App\Syllaby\Speeches\Jobs;

use Exception;
use Throwable;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use App\Syllaby\Speeches\Speech;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Syllaby\RealClones\RealClone;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Syllaby\Speeches\Vendors\Speaker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Syllaby\Speeches\Enums\SpeechStatus;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\RealClones\Enums\RealCloneStatus;

class TriggerSpeechGeneration implements ShouldQueue
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
        if (! $this->shouldGenerate()) {
            return;
        }

        try {
            Speaker::driver($this->speech->provider)->generate($this->speech, $this->clone);
        } catch (Exception $exception) {
            $this->fail($exception);
        }
    }

    /**
     * Handle a job failure.
     *
     * @throws Exception
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Unable to generate a speech for real clone {id} with {provider}', [
            'id' => $this->clone->id,
            'error' => $exception->getMessage(),
            'provider' => $this->speech->provider,
        ]);

        DB::transaction(function () {
            $this->refund($this->speech->user);
            $this->speech->update(['status' => SpeechStatus::FAILED, 'synced_at' => null]);
            $this->clone->update(['status' => RealCloneStatus::FAILED, 'synced_at' => null]);
        });
    }

    /**
     * Whether a speech generation process should start.
     */
    private function shouldGenerate(): bool
    {
        return Arr::get($this->clone->hash, 'speech') !== $this->clone->hashes('speech');
    }

    /**
     * Refunds user for both video and speech credits.
     */
    private function refund(User $user): void
    {
        $credits = new CreditService($user);

        $credits->refund($this->speech);
        $credits->refund($this->clone);
    }
}
