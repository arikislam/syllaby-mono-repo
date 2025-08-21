<?php

use RRule\RSet;
use Carbon\Carbon;
use App\Syllaby\Schedulers\Actions\CreateSchedulerAction;

beforeEach(function () {
    $this->action = new CreateSchedulerAction;

    Carbon::setTestNow('2025-01-06 00:00:00'); // Monday
});

test('it generates correct rrules for specific weekdays', function () {
    $input = [
        'start_date' => '2025-01-06', // Monday
        'days' => 14, // 2 weeks
        'weekdays' => ['TU', 'TH'], // Tuesday and Thursday
        'hours' => ['10:00', '14:00'],
    ];

    $rrules = $this->action->buildRrules($input);

    expect($rrules)->toHaveCount(2);

    $dates = collect($rrules)->flatMap(function ($rule) {
        return collect((new RSet($rule))->getOccurrences())->map(fn ($date) => Carbon::parse($date)->format('Y-m-d H:i'));
    })->sort()->values()->all();

    expect($dates)->toHaveCount(8)  // Should get 8 occurrences total (2 days per week × 2 weeks × 2 times per day)
        ->and($dates)->toBe([
            '2025-01-07 10:00', // Tuesday week 1
            '2025-01-07 14:00',
            '2025-01-09 10:00', // Thursday week 1
            '2025-01-09 14:00',
            '2025-01-14 10:00', // Tuesday week 2
            '2025-01-14 14:00',
            '2025-01-16 10:00', // Thursday week 2
            '2025-01-16 14:00',
        ]);
});

test('it handles short date ranges correctly', function () {
    $input = [
        'start_date' => '2025-01-08', // Wednesday
        'days' => 3, // Wed, Thu, Fri
        'weekdays' => ['TU', 'TH'], // Tuesday and Thursday
        'hours' => ['10:00'],
    ];

    $rrules = $this->action->buildRrules($input);

    $dates = collect($rrules)->flatMap(function ($rule) {
        return collect((new RSet($rule))->getOccurrences())->map(fn ($date) => Carbon::parse($date)->format('Y-m-d H:i'));
    })->sort()->values()->all();

    expect($dates)->toBe(['2025-01-09 10:00']); // Should only get Thursday since Tuesday is before start and Friday is not selected
});

test('it handles single weekday correctly', function () {
    $input = [
        'start_date' => '2025-01-06', // Monday
        'days' => 30, // Full month
        'weekdays' => ['WE'], // Only Wednesdays
        'hours' => ['09:00', '17:00'],
    ];

    $rrules = $this->action->buildRrules($input);

    $dates = collect($rrules)->flatMap(function ($rule) {
        return collect((new RSet($rule))->getOccurrences())->map(fn ($date) => Carbon::parse($date)->format('Y-m-d H:i'));
    })->sort()->values()->all();

    expect($dates)->toHaveCount(8) // Should get 4 Wednesdays × 2 times per day = 8 occurrences
        ->and($dates)->toBe([
            '2025-01-08 09:00', // Wednesday
            '2025-01-08 17:00',
            '2025-01-15 09:00', // Wednesday
            '2025-01-15 17:00',
            '2025-01-22 09:00', // Wednesday
            '2025-01-22 17:00',
            '2025-01-29 09:00', // Wednesday
            '2025-01-29 17:00',
        ]);
});

test('it respects the end date when generating occurrences', function () {
    $input = [
        'start_date' => '2025-01-06', // Monday
        'days' => 5, // Mon-Fri
        'weekdays' => ['MO', 'WE', 'FR'], // Mon, Wed, Fri
        'hours' => ['10:00'],
    ];

    $rrules = $this->action->buildRrules($input);

    $dates = collect($rrules)->flatMap(function ($rule) {
        return collect((new RSet($rule))->getOccurrences())->map(fn ($date) => Carbon::parse($date)->format('Y-m-d H:i'));
    })->sort()->values()->all();

    expect($dates)->toBe([
        '2025-01-06 10:00', // Monday
        '2025-01-08 10:00', // Wednesday
        '2025-01-10 10:00', // Friday
    ]);
});
