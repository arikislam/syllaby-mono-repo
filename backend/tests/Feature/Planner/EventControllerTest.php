<?php

namespace Tests\Feature\Planner;

use Carbon\Carbon;
use Pest\Expectation;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Video;
use App\Syllaby\Planner\Event;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('display all events for the user between a date range', function () {
    $user = User::factory()->create();

    $video = Video::factory()->for($user)->create();
    Event::factory()->for($video, 'model')->for($user)->count(5)->sequence(
        ['starts_at' => '2023-09-01 00:00:00', 'ends_at' => '2023-09-01 00:00:00'],
        ['starts_at' => '2023-08-31 00:00:00', 'ends_at' => '2023-08-31 00:00:00'],
        ['starts_at' => '2023-08-30 00:00:00', 'ends_at' => '2023-08-30 00:00:00'],
        ['starts_at' => '2023-08-29 00:00:00', 'ends_at' => '2023-08-29 00:00:00'],
        ['starts_at' => '2023-08-28 00:00:00', 'ends_at' => '2023-08-28 00:00:00']
    )->create();

    $this->assertDatabaseCount('events', 5);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/events?filter[date]=2023-08-29,2023-09-01&filter[type]=video');

    $response->assertOk();

    expect($response->json('data'))->toHaveCount(4);
});

it('uses the name of model as key in response', function () {
    $user = User::factory()->create();

    Event::factory()->for($user)->forEachSequence(
        ['model_type' => (new Video)->getMorphClass(), 'model_id' => Video::factory()->create()->id, 'starts_at' => now()],
        ['model_type' => (new Publication)->getMorphClass(), 'model_id' => Publication::factory()->create()->id, 'starts_at' => now()->subHour()]
    )->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/events?filter[type]=video,publication&include=model')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2)->sequence(
        fn (Expectation $json) => expect($json->value)->toHaveKey('video'),
        fn (Expectation $json) => expect($json->value)->toHaveKey('publication')
    );
});

test('Only one event is created for one publication batch', function () {
    // that means if user is publishing to three platforms, only one event is created
})->todo();

it('can filter a events by title', function () {
    //    $user = User::factory()->create();
    //
    //    $calendars = Calendar::factory()
    //        ->count(5)
    //        ->withTitle()
    //        ->sequence(
    //            ['user_id' => $user->id, 'title' => 'lorem', 'schedule' => '2023-09-01 00:00:00'],
    //            ['user_id' => $user->id, 'title' => 'ipsum', 'schedule' => '2023-08-31 00:00:00'],
    //            ['user_id' => $user->id, 'title' => 'dolor', 'schedule' => '2023-08-30 00:00:00'],
    //            ['user_id' => $user->id, 'title' => 'sit', 'schedule' => '2023-08-29 00:00:00'],
    //            ['user_id' => $user->id, 'title' => 'amet', 'schedule' => '2023-08-28 00:00:00']
    //        )->create();
    //
    //    $this->assertDatabaseCount(Calendar::class, 5);
    //
    //    $response = $this->actingAs($user, 'sanctum')
    //        ->getJson('v1/calendars?filter[text]=lorem,ipsum,dolor')
    //        ->assertOk();
    //
    //    $expected = $calendars->take(3)->pluck('id')->toArray();
    //
    //    $actual = array_column($response->json('data'), 'id');
    //
    //    expect($expected)->toBe($actual);
})->skip();

it('can filter events by title as well as by date', function () {
    //    /** @var User $user */
    //    $user = User::factory()->create();
    //
    //    $calendars = Calendar::factory()
    //        ->count(5)
    //        ->withTitle()
    //        ->sequence(
    //            ['user_id' => $user->id, 'title' => 'lorem', 'schedule' => '2023-09-01 00:00:00'],
    //            ['user_id' => $user->id, 'title' => 'ipsum', 'schedule' => '2023-08-31 00:00:00'],
    //            ['user_id' => $user->id, 'title' => 'dolor', 'schedule' => '2023-08-30 00:00:00'],
    //            ['user_id' => $user->id, 'title' => 'sit', 'schedule' => '2023-08-29 00:00:00'],
    //            ['user_id' => $user->id, 'title' => 'amet', 'schedule' => '2023-08-28 00:00:00']
    //        )->create();
    //
    //    $this->assertDatabaseCount(Calendar::class, 5);
    //
    //    $response = $this->actingAs($user, 'sanctum')
    //        ->getJson('v1/calendars?filter[text]=lorem,ipsum,dolor&filter[date]=2023-08-29,2023-09-01')
    //        ->assertOk();
    //
    //    $expected = $calendars->take(3)->pluck('id')->toArray();
    //
    //    $actual = array_column($response->json('data'), 'id');
    //
    //    expect($expected)->toBe($actual);
})->skip();

it('shows the events of only authenticated user', function () {
    $user = User::factory()->create();

    Event::factory()->count(4)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/calendars');

    expect($response->json('data'))->toBeEmpty();
});

it('can edit the date of a event', function () {
    Feature::define('calendar', true);

    Carbon::setTestNow('2023-01-01 00:00:00');

    $user = User::factory()->create();

    $format = config('common.iso_standard_format');
    $event = Event::factory()->for($user)->create([
        'starts_at' => now()->format($format),
        'ends_at' => now()->format($format),
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->patchJson("v1/events/{$event->id}", [
        'starts_at' => now()->addDay()->format($format),
        'ends_at' => now()->addDay()->addSeconds(2)->format($format),
    ]);

    $response->assertOk();
    expect($response->json('data'))
        ->starts_at->toBe(now()->addDay()->toJSON())
        ->ends_at->toBe(now()->addDay()->addSeconds(2)->toJSON());
});

it('fails when updating another events', function () {
    Feature::define('calendar', true);

    Carbon::setTestNow('2023-01-01 00:00:00');

    $user = User::factory()->create();

    $format = config('common.iso_standard_format');
    $event = Event::factory()->create([
        'starts_at' => now()->format($format),
        'ends_at' => now()->format($format),
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->patchJson("v1/events/{$event->id}", [
        'starts_at' => now()->addDay()->format($format),
    ]);

    $response->assertForbidden();
});

it('can delete an event and its related model', function () {
    Feature::define('calendar', true);

    $user = User::factory()->create();

    $event = Event::factory()->for($user)
        ->for($publication = Publication::factory()->for($user)->create(), 'model')
        ->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("v1/events/{$event->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('events', ['id' => $event->id]);
    $this->assertDatabaseMissing('publications', ['id' => $publication->id]);
});

it('fails to delete another user events', function () {
    Feature::define('calendar', true);

    $event = Event::factory()->create();

    $this->actingAs(User::factory()->create());
    $response = $this->deleteJson("v1/events/{$event->id}");

    $response->assertForbidden();
});

it('deletes the details of publication when deleting the event', function () {
    Feature::define('calendar', true);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->youtube()->for($user)->createQuietly();

    $publication = Publication::factory()->permanent()->recycle($user)->create();

    SocialChannel::factory()->for($account, 'account')->hasAttached($publication, [
        'status' => SocialUploadStatus::COMPLETED,
        'provider_media_id' => '123',
    ])->create();

    $event = Event::factory()->for($user)->for($publication, 'model')->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("v1/events/{$event->id}")
        ->assertNoContent();

    $this->assertDatabaseEmpty('events');
    $this->assertDatabaseEmpty('publications');
    $this->assertDatabaseEmpty('account_publications');
});
