<?php

namespace Tests\Feature\Schedulers;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use App\Syllaby\Generators\Generator;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\Occurrence;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Schedulers\Enums\SchedulerSource;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can create a scheduler with its base information', function () {
    Carbon::setTestNow('2024-06-01 10:00:00');

    $user = User::factory()->create();

    $account = SocialAccount::factory()->for($user)->tiktok();
    $tiktok = SocialChannel::factory()->for($account, 'account')->create();

    $this->actingAs($user);
    $response = $this->postJson('/v1/schedulers', [
        'topic' => 'My first topic',
        'social_channels' => [$tiktok->id],
        'days' => 4,
        'times_per_day' => 4,
        'hours' => ['11:10', '12:15', '13:20', '14:25'],
        'start_date' => '2025-01-06',
        'weekdays' => ['MO', 'TU', 'WE', 'TH'],
        'ai_labels' => true,
        'custom_description' => 'My first custom description',
    ]);

    $scheduler = Scheduler::first();

    $response->assertCreated();
    $response->assertJsonFragment([
        'user_id' => $user->id,
        'topic' => 'My first topic',
        'title' => 'My first topic',
        'source' => SchedulerSource::AI->value,
        'status' => SchedulerStatus::DRAFT->value,
        'metadata' => [
            'ai_labels' => true,
            'custom_description' => 'My first custom description',
        ],
    ]);

    expect($response->json('data.details.days'))->toBe(4)
        ->and($response->json('data.details.times_per_day'))->toBe(4)
        ->and($response->json('data.details.occurrences'))->toHaveCount(4)
        ->and($response->json('data.details.hours'))->toBe(['11:10', '12:15', '13:20', '14:25']);

    $this->assertDatabaseHas('schedulers', [
        'user_id' => $user->id,
        'source' => SchedulerSource::AI->value,
        'status' => SchedulerStatus::DRAFT->value,
        'metadata->ai_labels' => true,
        'metadata->custom_description' => 'My first custom description',
    ]);

    $this->assertDatabaseHas('scheduler_social_channel', [
        'social_channel_id' => $tiktok->id,
        'scheduler_id' => $scheduler->id,
    ]);
});

it('can create a scheduler with a CSV file', function () {
    Carbon::setTestNow('2024-06-01 10:00:00');

    $user = User::factory()->create();

    $account = SocialAccount::factory()->for($user)->tiktok();
    $tiktok = SocialChannel::factory()->for($account, 'account')->create();

    $this->actingAs($user);
    $response = $this->postJson('/v1/schedulers', [
        'social_channels' => [$tiktok->id],
        'days' => 3,
        'times_per_day' => 1,
        'hours' => ['11:10'],
        'start_date' => '2025-01-06',
        'weekdays' => ['MO', 'TU', 'WE'],
        'ai_labels' => false,
        'custom_description' => 'My first custom description',
        'csv' => [
            [
                'title' => 'My first title',
                'script' => 'My first script',
            ],
            [
                'title' => 'My second title',
                'script' => 'My second script',
            ],
            [
                'title' => 'My third title',
                'script' => 'My third script',
            ],
        ],
    ]);

    $response->assertCreated();

    $response->assertJsonFragment([
        'user_id' => $user->id,
        'source' => SchedulerSource::CSV->value,
        'status' => SchedulerStatus::REVIEWING->value,
    ]);

    $this->assertDatabaseCount('scheduler_occurrences', 3);
});

it('can list schedulers for the authenticated user', function () {
    $user = User::factory()->create();

    Scheduler::factory()->count(3)->for($user)->create();

    $this->actingAs($user);
    $response = $this->getJson('/v1/schedulers');

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'user_id',
                'idea_id',
                'status',
                'created_at',
                'updated_at',
            ],
        ],
    ]);

    $response->assertJsonCount(3, 'data');
});

it('can show a given scheduler details', function () {
    $user = User::factory()->create();

    $scheduler = Scheduler::factory()->for($user)->create();

    $this->actingAs($user);
    $response = $this->getJson("/v1/schedulers/{$scheduler->id}");

    $response->assertOk();
    $response->assertJsonFragment([
        'id' => $scheduler->id,
    ]);
});

it('can include occurrences grouped by date in the scheduler details', function () {
    $user = User::factory()->create();

    Carbon::setTestNow('2024-06-01 10:00:00');

    $scheduler = Scheduler::factory()->for($user)->create();
    Occurrence::factory()->for($scheduler)->count(4)->sequence(
        ['occurs_at' => now()->addDays(1)->addHours(4)],
        ['occurs_at' => now()->addDays(1)->addHours(8)],
        ['occurs_at' => now()->addDays(2)->addHours(12)],
        ['occurs_at' => now()->addDays(2)->addHours(13)],
    )->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->getJson("/v1/schedulers/{$scheduler->id}?include=occurrences");

    $response->assertOk();
    $response->assertJsonFragment([
        'id' => $scheduler->id,
    ]);

    $response->assertJsonCount(2, 'data.occurrences.2024-06-02');
    $response->assertJsonCount(2, 'data.occurrences.2024-06-03');
});

it('can update a scheduler and its recurrence rules', function () {
    Carbon::setTestNow('2024-10-01');

    $user = User::factory()->create();

    $scheduler = Scheduler::factory()->for($user)->create([
        'title' => 'My first scheduler',
        'status' => SchedulerStatus::REVIEWING,
        'rrules' => [
            "DTSTART:20241002T120000Z\nRRULE:FREQ=DAILY;COUNT=3",
            "DTSTART:20241002T130000Z\nRRULE:FREQ=DAILY;COUNT=3",
            "DTSTART:20241002T140000Z\nRRULE:FREQ=DAILY;COUNT=3",
        ],
        'metadata' => [
            'ai_labels' => true,
            'custom_description' => 'My first custom description',
        ],
    ]);

    Occurrence::factory()->recycle($scheduler)->count(3)
        ->has(Generator::factory())
        ->create();

    $this->assertDatabaseCount('scheduler_occurrences', 3);

    $account = SocialAccount::factory()->for($user)->tiktok();
    $tiktok = SocialChannel::factory()->for($account, 'account')->create();

    $this->actingAs($user);
    $response = $this->patchJson("/v1/schedulers/{$scheduler->id}", [
        'topic' => 'My updated scheduler',
        'social_channels' => [$tiktok->id],
        'days' => 2,
        'times_per_day' => 2,
        'hours' => ['12:00', '16:00'],
        'start_date' => '2024-10-03',
        'weekdays' => ['MO', 'TU'],
        'ai_labels' => false,
        'custom_description' => 'My updated custom description',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('schedulers', [
        'title' => 'My updated scheduler',
        'metadata->ai_labels' => false,
        'metadata->custom_description' => 'My updated custom description',
    ]);

    $this->assertDatabaseCount('scheduler_occurrences', 0);
});

it('fails to fetch another user scheduler', function () {
    $user = User::factory()->create();

    Carbon::setTestNow('2024-06-01 10:00:00');

    $scheduler = Scheduler::factory()->create();

    $this->actingAs($user);
    $response = $this->getJson("/v1/schedulers/{$scheduler->id}");

    $response->assertNotFound();
});
