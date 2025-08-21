<?php

namespace Tests\Feature\Planner;

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use Illuminate\Support\Carbon;
use App\Syllaby\Planner\Event;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it(' can toggle the complete status of an event', function () {
    Feature::define('calendar', true);

    Carbon::setTestNow('2023-01-01 00:00:00');

    $user = User::factory()->create();

    $event = Event::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum');

    $response = $this->putJson("v1/events/$event->id/completes");
    expect($response->json('data'))->completed_at->toBe(now()->toJSON());

    $response = $this->patchJson("v1/events/$event->id/completes");
    expect($response->json('data'))->completed_at->toBe(null);
});

it('fails to toggle the complete status of another user event', function () {
    Feature::define('calendar', true);

    $event = Event::factory()->create();

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->putJson("v1/events/$event->id/completes")
        ->assertForbidden();
});
