<?php

namespace App\Syllaby\Clonables\Jobs;

use Throwable;
use App\Syllaby\Users\User;
use Illuminate\Bus\Queueable;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Clonables\Clonable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Clonables\Enums\CloneStatus;
use App\Syllaby\Clonables\Vendors\Voices\Recorder;

class ProcessClonedVoiceJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected User $user,
        protected Clonable $clonable,
        protected Voice $voice
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! Recorder::driver($this->voice->provider)->clone($this->voice, $this->clonable)) {
            $this->release(10);

            return;
        }

        $this->clonable->update([
            'status' => CloneStatus::COMPLETED,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Unable to clone voice with {provider}', [
            'error' => $exception->getMessage(),
            'provider' => $this->voice->provider,
        ]);

        $this->clonable->update([
            'status' => CloneStatus::FAILED,
        ]);
    }
}
