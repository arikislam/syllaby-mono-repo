<?php

namespace Tests\Feature\Users;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Subscriptions\Plan;
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

it('it shows the current used and total schedule publications for a user', function () {
    Feature::define('max_scheduled_posts', 2);

    Carbon::setTestNow(now());

    $plan = tap(Plan::factory()->price()->create(), function ($plan) {
        config(['syllaby.plans.basic.monthly_prices' => [$plan->plan_id]]);
    });

    $user = User::factory()->withSubscription($plan)->create();

    $tiktok = SocialAccount::factory()->tiktok()->create(['user_id' => $user->id]);

    $tiktokChannel = SocialChannel::factory()->individual()->create(['social_account_id' => $tiktok->id]);

    $scheduled = Publication::factory()->permanent()->scheduled(now()->addDay())->for($user)->create();

    $scheduled->channels()->newPivotStatement()->insert([
        'social_channel_id' => $tiktokChannel->id,
        'publication_id' => $scheduled->id,
        'status' => SocialUploadStatus::SCHEDULED->value,
        'metadata' => json_encode([]),
    ]);

    $notScheduled = Publication::factory()->permanent()->for($user)->create();

    $notScheduled->channels()->newPivotStatement()->insert([
        'social_channel_id' => $tiktokChannel->id,
        'publication_id' => $notScheduled->id,
        'status' => SocialUploadStatus::COMPLETED->value,
        'metadata' => json_encode([]),
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/user/publications-usage');

    $response->assertJsonFragment(['data' => [
        'used' => 1,
        'total' => Feature::value('max_scheduled_posts'),
    ]]);
});
