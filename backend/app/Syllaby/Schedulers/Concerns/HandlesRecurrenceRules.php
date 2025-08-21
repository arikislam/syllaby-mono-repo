<?php

namespace App\Syllaby\Schedulers\Concerns;

use RRule\RRule;
use Carbon\Carbon;
use Illuminate\Support\Arr;

trait HandlesRecurrenceRules
{
    public function build(array $input): array
    {
        $start = Carbon::parse($input['start_date']);

        $end = $start->copy()->addDays($input['days'] - 1)->endOfDay();

        return Arr::map($input['hours'], function ($hour) use ($input, $start, $end) {
            $time = Carbon::createFromFormat('H:i', $hour);

            return (new RRule([
                'FREQ' => RRule::WEEKLY,
                'INTERVAL' => 1,
                'UNTIL' => $end,
                'BYDAY' => collect($input['weekdays'])->join(','),
                'DTSTART' => $start->copy()->setTime($time->hour, $time->minute),
            ]))->rfcString();
        });
    }
}
