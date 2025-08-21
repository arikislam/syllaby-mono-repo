<?php

namespace App\Syllaby\Surveys\Events;

use App\Syllaby\Users\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class BusinessIntakeFormAnswered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public User $user, public string $industry)
    {
        //
    }
}
