<?php

namespace App\Syllaby\Credits\Events;

use App\Syllaby\Credits\CreditHistory;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;

class CreditsRefunded implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    public function __construct(public CreditHistory $history) {}
}
