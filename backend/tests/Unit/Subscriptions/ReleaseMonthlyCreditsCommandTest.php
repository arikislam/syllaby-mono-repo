<?php

namespace Tests\Unit\Subscriptions;

use Carbon\Carbon;
use Stripe\Subscription;
use App\Syllaby\Users\User;
use Illuminate\Support\Str;
use App\Syllaby\Subscriptions\Plan;
use App\Syllaby\Credits\CreditEvent;
use App\Syllaby\Credits\CreditHistory;
use Database\Seeders\CreditEventTableSeeder;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Sequence;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CreditEventTableSeeder::class);
});

it('successfully releases monthly credits to users on the yearly plans', function () {
    Carbon::setTestNow('2024-01-01');

    $events = CreditEvent::whereIn('name', [
        CreditEventEnum::MONTHLY_CREDITS_ADDED->value,
        CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID->value,
    ])->get();

    $creditsAdded = $events->firstWhere('name', CreditEventEnum::MONTHLY_CREDITS_ADDED->value);
    $subscriptionAmountPaid = $events->firstWhere('name', CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID->value);

    [$basic, $standard, $premium] = Plan::factory()->count(3)->product()->create();
    config(['services.stripe.products' => [$basic->plan_id, $standard->plan_id, $premium->plan_id]]);

    $yearly = Plan::factory()->count(3)->price('year')->state(new Sequence(
        ['parent_id' => $basic->id],
        ['parent_id' => $standard->id],
        ['parent_id' => $premium->id],
    ))->create();

    $john = User::factory()->create([
        'name' => 'John Doe',
        'remaining_credit_amount' => 200,
        'monthly_credit_amount' => 500,
        'plan_id' => $yearly[0]->id,
    ]);

    $john->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_'.Str::random(10),
        'stripe_status' => Subscription::STATUS_ACTIVE,
        'stripe_price' => $yearly[0]->plan_id,
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
        'cycle_anchor_at' => now()->addMonth(), // 2024-02-01
    ]);

    CreditHistory::factory()->create([
        'user_id' => $john->id,
        'credit_events_id' => $subscriptionAmountPaid->id,
        'amount' => 500,
        'created_at' => now()->addMonth(),
        'updated_at' => now()->addMonth(),
    ]);

    $jane = User::factory()->create([
        'name' => 'Jane Doe',
        'remaining_credit_amount' => 800,
        'monthly_credit_amount' => 1000,
        'plan_id' => $yearly[1]->id,
    ]);

    $jane->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_'.Str::random(10),
        'stripe_status' => Subscription::STATUS_ACTIVE,
        'stripe_price' => $yearly[1]->plan_id,
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
        'cycle_anchor_at' => now()->addDays(10)->addMonths(3), // 2024-04-10
    ]);

    CreditHistory::factory()->create([
        'user_id' => $jane->id,
        'credit_events_id' => $subscriptionAmountPaid->id,
        'amount' => 1000,
        'created_at' => now()->addDays(10)->addMonths(3),
        'updated_at' => now()->addDays(10)->addMonths(3),
    ]);

    $this->artisan('syllaby:release-credits')->assertExitCode(0);

    $this->assertDatabaseHas('users', ['id' => $john->id, 'remaining_credit_amount' => 200]);
    $this->assertDatabaseHas('users', ['id' => $jane->id, 'remaining_credit_amount' => 800]);

    $this->travelTo(now()->addMonth()); // 2024-02-01
    $this->artisan('syllaby:release-credits')->assertExitCode(0);
    $this->assertDatabaseHas('users', ['id' => $john->id, 'remaining_credit_amount' => 200]);

    $this->travelTo(now()->addMonth(2)); // 2024-03-01
    $this->artisan('syllaby:release-credits')->assertExitCode(0);
    $this->assertDatabaseHas('users', ['id' => $john->id, 'remaining_credit_amount' => 700]);

    $this->travelTo(now()->addMonths(3)->addDays(10)); // 2024-04-10
    $this->artisan('syllaby:release-credits')->assertExitCode(0);
    $this->assertDatabaseHas('users', ['id' => $jane->id, 'remaining_credit_amount' => 1800]);
});
