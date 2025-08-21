<?php

namespace App\Feature\Subscriptions;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Support\Facades\Notification;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Subscriptions\Actions\ExtendTrialAction;
use App\Syllaby\Subscriptions\Notifications\TrialExtended;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can extend a user trial period', function () {
    Notification::fake();
    Carbon::setTestNow(Carbon::create(2023, 9, 7));

    $plan = Plan::factory()->price()->create();
    $user = User::factory()->withTrial($plan, days: 7)->create();

    $this->partialMock(ExtendTrialAction::class)->shouldReceive('extendTrial')->andReturn(true);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('v1/subscriptions/extend-trial');

    Notification::assertSentTo($user, TrialExtended::class);
    $this->assertEquals(now()->addDays(7), $user->subscription()->trial_ends_at);
    $response->assertOk();
});

it('can not extend twice a user trial period', function () {
    Notification::fake();

    Carbon::setTestNow(Carbon::create(2023, 9, 7));

    $plan = Plan::factory()->price()->create();
    $user = User::factory()->withTrial($plan, days: 7)->create();

    $this->travel(2)->days();

    tap($user->subscription(), function ($subscription) {
        $subscription->update(['trial_ends_at' => $subscription->trial_ends_at->addDays(7)]);
    });

    $this->partialMock(ExtendTrialAction::class)->shouldReceive('extendTrial')->andReturn(true);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('v1/subscriptions/extend-trial');

    Notification::assertNotSentTo($user, TrialExtended::class);
    $response->assertBadRequest();
});
