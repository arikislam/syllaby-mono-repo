<?php

namespace Tests\Feature\Publisher\Publication;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use Database\Seeders\MetricsTableSeeder;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Metrics\PublicationMetricKey;
use App\Syllaby\Publisher\Metrics\PublicationMetricValue;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('user cant see the details without logging in', function () {
    $publication = Publication::factory()->create();

    $this->getJson("v1/publications/{$publication->id}/channels/1")->assertUnauthorized();
});

it('should return a list of metrics for a publication channel', function () {
    Carbon::setTestNow('2023-01-10 00:00:00');

    $this->seed(MetricsTableSeeder::class);

    $user = User::factory()->create();

    $publication = Publication::factory()->permanent()->for($user)->create();

    $tiktok = SocialChannel::factory()->for(SocialAccount::factory()->for($user)->tiktok(), 'account')->hasAttached($publication, [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'tiktok-dummy-id',
    ])->create();

    PublicationMetricValue::factory()->for($tiktok, 'channel')
        ->for($tiktok->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->count(3)
        ->sequence(
            ['value' => 9000, 'created_at' => now()->subDays(3)],
            ['value' => 10000, 'created_at' => now()->subDays(2)],
            ['value' => 12000, 'created_at' => now()->subDays()],
        )->create();

    PublicationMetricValue::factory()->for($tiktok, 'channel')
        ->for($tiktok->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'likes-count')->first(), 'key')
        ->count(3)
        ->sequence(
            ['value' => 24, 'created_at' => now()->subDays(3)],
            ['value' => 30, 'created_at' => now()->subDays(2)],
            ['value' => 40, 'created_at' => now()->subDays()],
        )->create();

    $response = $this->actingAs($user)->getJson("v1/publications/{$publication->id}/channels/{$tiktok->id}?include=metrics")->assertOk();

    expect($response->json('data.metrics.views'))
        ->toHaveCount(3)
        ->sequence(
            fn ($keys) => expect($keys->value['value'])->toBe(9000)->and($keys->value['date'])->not()->toBeNull(),
            fn ($keys) => expect($keys->value['value'])->toBe(1000)->and($keys->value['date'])->not()->toBeNull(),
            fn ($keys) => expect($keys->value['value'])->toBe(2000)->and($keys->value['date'])->not()->toBeNull(),
        )
        ->and($response->json('data.metrics.likes'))
        ->toHaveCount(3)
        ->sequence(
            fn ($keys) => expect($keys->value['value'])->toBe(24)->and($keys->value['date'])->not()->toBeNull(),
            fn ($keys) => expect($keys->value['value'])->toBe(6)->and($keys->value['date'])->not()->toBeNull(),
            fn ($keys) => expect($keys->value['value'])->toBe(10)->and($keys->value['date'])->not()->toBeNull(),
        )
        ->and($response->json('data.metrics.comments'))->toBeEmpty()
        ->and($response->json('data.aggregate.views-count'))->toBe(12000)
        ->and($response->json('data.aggregate.likes-count'))->toBe(40)
        ->and($response->json('data.aggregate.comments-count'))->toBe(0);
})->skip("Metrics are hidden for now, so this test is not applicable until we reintroduce them.");

it('should not include metrics for non-requested channels', function () {
    $this->seed(MetricsTableSeeder::class);

    $user = User::factory()->create();

    $publication = Publication::factory()->permanent()->for($user)->create();

    $tiktok = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->tiktok(), 'account'
    )->hasAttached($publication, [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'tiktok-dummy-id',
    ])->create();

    $youtube = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->youtube(), 'account'
    )->hasAttached($publication, [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'youtube-dummy-id',
    ])->create();

    PublicationMetricValue::factory()->for($tiktok, 'channel')
        ->for($tiktok->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->create(['value' => 12000]);

    PublicationMetricValue::factory()->for($youtube, 'channel')
        ->for($youtube->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->create(['value' => 9000]);

    $response = $this->actingAs($user)->getJson("v1/publications/{$publication->id}/channels/{$youtube->id}?include=metrics")->assertOk();

    expect($response->json('data.metrics.views'))->toHaveCount(1)->sequence(
        fn ($keys) => expect($keys->value['value'])->toBe(9000)->and($keys->value['date'])->not()->toBeNull(),
    )
        ->and($response->json('data.metrics.likes'))->toBeEmpty()
        ->and($response->json('data.metrics.comments'))->toBeEmpty()
        ->and($response->json('data.aggregate.views-count'))->toBe(9000)
        ->and($response->json('data.aggregate.likes-count'))->toBe(0)
        ->and($response->json('data.aggregate.comments-count'))->toBe(0);
})->skip("Metrics are hidden for now, so this test is not applicable until we reintroduce them.");

it('can filter metrics of a channel by date', function () {
    Carbon::setTestNow('2023-01-10 00:00:00');

    $this->seed(MetricsTableSeeder::class);

    $user = User::factory()->create();

    $publication = Publication::factory()->permanent()->for($user)->create();

    $tiktok = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->tiktok(), 'account'
    )->hasAttached($publication, [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'tiktok-dummy-id',
    ])->create();

    PublicationMetricValue::factory()->for($tiktok, 'channel')
        ->for($tiktok->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->count(4)
        ->sequence(
            ['value' => 900, 'created_at' => now()->subDays(3)],
            ['value' => 1000, 'created_at' => now()->subDays(2)],
            ['value' => 1200, 'created_at' => now()->subDays()],
            ['value' => 1500, 'created_at' => now()],
        )->create();

    $response = $this->actingAs($user)
        ->getJson("v1/publications/{$publication->id}/channels/{$tiktok->id}?include=metrics&filter[date]=2023-01-09,2023-01-10")
        ->assertOk();

    expect($response->json('data.metrics.views'))
        ->toHaveCount(2)
        ->sequence(
            fn ($keys) => expect($keys->value['value'])->toBe(1200)->and($keys->value['date'])->not()->toBeNull(),
            fn ($keys) => expect($keys->value['value'])->toBe(300)->and($keys->value['date'])->not()->toBeNull(),
        )
        ->and($response->json('data.metrics.likes'))->toBeEmpty()
        ->and($response->json('data.metrics.comments'))->toBeEmpty()
        ->and($response->json('data.aggregate.views-count'))->toBe(1500)
        ->and($response->json('data.aggregate.likes-count'))->toBe(0)
        ->and($response->json('data.aggregate.comments-count'))->toBe(0);
})->skip("Metrics are hidden for now, so this test is not applicable until we reintroduce them.");

it('applies a default filter of 7 days', function () {
    Carbon::setTestNow('2023-01-10 00:00:00');

    $this->seed(MetricsTableSeeder::class);

    $user = User::factory()->create();

    $publication = Publication::factory()->permanent()->for($user)->create();

    $tiktok = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->tiktok(), 'account'
    )->hasAttached($publication, [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'tiktok-dummy-id',
    ])->create();

    PublicationMetricValue::factory()->for($tiktok, 'channel')
        ->for($tiktok->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->count(4)
        ->sequence(
            ['value' => 900, 'created_at' => now()->subDays(14)],
            ['value' => 1000, 'created_at' => now()->subDays(13)],
            ['value' => 1200, 'created_at' => now()->subDays(3)],
            ['value' => 1500, 'created_at' => now()->subDays(2)],
        )->create();

    $response = $this->actingAs($user)->getJson("v1/publications/{$publication->id}/channels/{$tiktok->id}?include=metrics")->assertOk();

    expect($response->json('data.metrics.views'))
        ->toHaveCount(2)
        ->sequence(
            fn ($keys) => expect($keys->value['value'])->toBe(1200)->and($keys->value['date'])->not()->toBeNull(),
            fn ($keys) => expect($keys->value['value'])->toBe(300)->and($keys->value['date'])->not()->toBeNull(),
        )
        ->and($response->json('data.metrics.likes'))->toBeEmpty()
        ->and($response->json('data.metrics.comments'))->toBeEmpty()
        ->and($response->json('data.aggregate.views-count'))->toBe(1500)
        ->and($response->json('data.aggregate.likes-count'))->toBe(0)
        ->and($response->json('data.aggregate.comments-count'))->toBe(0);
})->skip("Metrics are hidden for now, so this test is not applicable until we reintroduce them.");

it('should not return results of other users', function () {
    $user = User::factory()->create();

    $publication = Publication::factory()->permanent()->for($user)->create();

    $tiktok = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->tiktok(), 'account'
    )->hasAttached($publication, [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'tiktok-dummy-id',
    ])->create();

    $this->actingAs(User::factory()->create())->getJson("v1/publications/{$publication->id}/channels/{$tiktok->id}?include=metrics")->assertForbidden();
});

it('should not return results if post is not published', function () {
    $this->seed(MetricsTableSeeder::class);

    $user = User::factory()->create();

    $publication = Publication::factory()->permanent()->for($user)->create();

    $tiktok = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->tiktok(), 'account'
    )->hasAttached($publication, [
        'status' => SocialUploadStatus::FAILED->value,
        'provider_media_id' => 'tiktok-dummy-id',
    ])->create();

    $this->actingAs($user)->getJson("v1/publications/{$publication->id}/channels/{$tiktok->id}?include=metrics")->assertUnprocessable();
});

it('should not return results for invalid input', function () {
    $user = User::factory()->create();

    $publication = Publication::factory()->permanent()->for($user)->create();

    $tiktok = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->tiktok(), 'account'
    )->hasAttached(Publication::factory()->for($user), [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'tiktok-dummy-id',
    ])->create();

    $this->actingAs($user)->getJson("v1/publications/{$publication->id}/channels/{$tiktok->id}?include=metrics")->assertUnprocessable();
});

it('must contain metrics in include for filters to be applied correctly', function () {
    $user = User::factory()->create();

    $publication = Publication::factory()->permanent()->for($user)->create();

    $tiktok = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->tiktok(), 'account'
    )->hasAttached($publication, [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'tiktok-dummy-id',
    ])->create();

    $this->actingAs($user)->getJson("v1/publications/{$publication->id}/channels/{$tiktok->id}?filter[date]=2023-01-09,2023-01-10")->assertUnprocessable();
});
