<?php

namespace App\Syllaby\Subscriptions\Events;

use Illuminate\Queue\SerializesModels;
use App\Syllaby\Subscriptions\GooglePlayRtdn;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class GooglePlayRtdnHandled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The Google Play RTDN model instance.
     */
    public GooglePlayRtdn $rtdn;

    /**
     * Create a new event instance.
     */
    public function __construct(GooglePlayRtdn $rtdn)
    {
        $this->rtdn = $rtdn;
    }
}
