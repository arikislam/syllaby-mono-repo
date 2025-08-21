<?php

namespace App\Syllaby\Subscriptions\Events;

use Illuminate\Queue\SerializesModels;
use App\Syllaby\Subscriptions\JVZooPurchase;
use Illuminate\Foundation\Events\Dispatchable;

class JVZooPurchaseReceived
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public JVZooPurchase $purchase, public array $payload) {}
}
