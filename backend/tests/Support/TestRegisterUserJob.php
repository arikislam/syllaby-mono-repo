<?php

namespace Tests\Support;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Syllaby\Auth\Actions\RegistrationAction;

/**
 * This job is used as a utility to reproduce race condition in registration.
 */
class TestRegisterUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected array $data)
    {
    }

    public function handle(): void
    {
        app(RegistrationAction::class)->handle($this->data);
    }
}