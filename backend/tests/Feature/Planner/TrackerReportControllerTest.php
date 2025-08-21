<?php

namespace Tests\Feature\Planner;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use App\Syllaby\Planner\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can fetch the consistency report of the user', function () {
    Carbon::setTestNow(Carbon::create(2023, 9, 10));

    $user = User::factory()->create();

    Event::factory()->for($user)->count(5)->create([
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay(),
        'completed_at' => now()->addDay(),
    ]);

    Event::factory()->for($user)->count(5)->create([
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay(),
        'completed_at' => null,
    ]);

    $this->assertDatabaseCount('events', 10);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/events/tracker-reports');

    $response->assertOk();
    $response->assertJsonFragment([
        'total' => 10,
        'completed' => 5,
        'percentage' => 50,
    ]);
});

it('can fetch consistency report of the user with start and end date', function () {
    $user = User::factory()->create();

    Event::factory()->for($user)->count(5)->create([
        'starts_at' => '2023-01-01 00:00:00',
        'ends_at' => '2023-01-01 00:00:00',
        'completed_at' => now()->addDay(),
    ]);

    Event::factory()->for($user)->count(5)->create([
        'starts_at' => '2023-01-31 00:00:00',
        'ends_at' => '2023-01-31 00:00:00',
        'completed_at' => null,
    ]);

    $this->assertDatabaseCount('events', 10);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/events/tracker-reports?start_date=2023-01-01&end_date=2023-01-20');

    $response->assertOk();
    $response->assertJsonFragment([
        'total' => 5,
        'completed' => 5,
        'percentage' => 100,
    ]);
});

it('can fetch the consistency report of the user if he has no contents', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/events/tracker-reports');
    $response->assertOk();
    $response->assertJsonFragment([
        'total' => 0,
        'completed' => 0,
        'percentage' => 0,
    ]);
});
