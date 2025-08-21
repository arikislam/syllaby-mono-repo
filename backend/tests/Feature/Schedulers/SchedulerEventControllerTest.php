<?php

namespace Tests\Feature\Schedulers;

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Planner\Event;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Database\Eloquent\Relations\Relation;

uses(RefreshDatabase::class);

it('deletes all events and associated publications', function () {
    Feature::define('calendar', true);

    $user = User::factory()->create();
    $scheduler = Scheduler::factory()->for($user)->create();
    $publications = Publication::factory()->count(3)->recycle($user)->create();

    Event::factory()->for($user)->create();
    Event::factory()->count(3)->for($scheduler)->for($user)->sequence(
        ['model_id' => $publications[0]->id, 'model_type' => Relation::getMorphAlias(Publication::class)],
        ['model_id' => $publications[1]->id, 'model_type' => Relation::getMorphAlias(Publication::class)],
        ['model_id' => $publications[2]->id, 'model_type' => Relation::getMorphAlias(Publication::class)],
    )->create();

    $this->assertDatabaseCount('events', 4);
    $this->assertDatabaseCount('publications', 3);

    $this->actingAs($user);
    $response = $this->deleteJson("/v1/schedulers/{$scheduler->id}/events");

    $response->assertNoContent();

    $this->assertDatabaseHas('schedulers', [
        'id' => $scheduler->id,
        'status' => SchedulerStatus::DELETED->value,
    ]);

    $this->assertDatabaseCount('events', 1);
    $this->assertDatabaseCount('publications', 0);
});
