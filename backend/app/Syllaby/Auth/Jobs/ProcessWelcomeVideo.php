<?php

namespace App\Syllaby\Auth\Jobs;

use Exception;
use Throwable;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use App\System\Enums\QueueType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use App\Syllaby\Auth\Mails\WelcomeEmail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Syllaby\Users\Actions\TransferWelcomeVideoAction;

class ProcessWelcomeVideo implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Webhook video details.
     */
    protected array $input;

    /**
     * Webhook video render status
     */
    protected RealCloneStatus $status;

    /**
     * Create a new job instance.
     */
    public function __construct(array $input, RealCloneStatus $status)
    {
        $this->input = $input;
        $this->status = $status;

        $this->onConnection('videos');
        $this->onQueue(QueueType::RENDER->value);
    }

    /**
     * Execute the job.
     */
    public function handle(TransferWelcomeVideoAction $transfer): void
    {
        $user = $this->resolveUser();

        if ($this->status !== RealCloneStatus::COMPLETED) {
            $this->fail(new Exception("Heygen Welcome Video Failed for user: {$user->id}"));
        }

        if (!$transfer->handle($user, Arr::get($this->input, 'video_url'))) {
            $this->fail(new Exception("Unable to download welcome video for user: {$user->id}"));
        }

        $this->sendWelcomeEmail($user);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        if (!($exception instanceof ModelNotFoundException)) {
            $user = $this->resolveUser();
            $this->sendWelcomeEmail($user);
        }

        Log::error($exception->getMessage());
    }

    /**
     * Triggers the user welcome email.
     */
    private function sendWelcomeEmail(User $user): void
    {
        Mail::to($user->email)->send(new WelcomeEmail($user));
    }

    /**
     * Fetch the registered user.
     *
     * @throws ModelNotFoundException
     */
    private function resolveUser(): User
    {
        return User::findOrFail(Arr::get($this->input, 'metadata.user_id'));
    }
}
