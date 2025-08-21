<?php

namespace App\Syllaby\Planner\Actions;

use Illuminate\Support\Arr;
use App\Syllaby\Planner\Event;

class UpdateEventAction
{
    /**
     * Updates the given event in storage.
     */
    public function handle(Event $event, array $input): Event
    {
        return tap($event)->update([
            'color' => Arr::get($input, 'color', $event->color),
            'starts_at' => Arr::get($input, 'starts_at', $event->starts_at),
            'ends_at' => Arr::get($input, 'ends_at', $event->ends_at),
        ]);
    }
}
