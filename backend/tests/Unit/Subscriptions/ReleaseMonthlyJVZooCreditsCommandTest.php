<?php

namespace Tests\Unit\Subscriptions;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use App\Syllaby\Subscriptions\Plan;
use App\Syllaby\Credits\CreditEvent;
use App\Syllaby\Credits\CreditHistory;
use App\Syllaby\Subscriptions\JVZooPlan;
use Database\Seeders\CreditEventTableSeeder;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Subscriptions\JVZooSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Sequence;
use App\Syllaby\Subscriptions\Enums\SubscriptionProvider;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CreditEventTableSeeder::class);
});

it('successfully releases monthly credits to users on yearly JVZoo plans', function () {
    Carbon::setTestNow('2024-01-01');

    $events = CreditEvent::whereIn('name', [
        CreditEventEnum::MONTHLY_CREDITS_ADDED->value,
        CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID->value,
    ])->get();

    $creditsAdded = $events->firstWhere('name', CreditEventEnum::MONTHLY_CREDITS_ADDED->value);
    $subscriptionAmountPaid = $events->firstWhere('name', CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID->value);

    [$basic, $standard, $premium] = Plan::factory()->count(3)->price('year')->create();

    $yearly = JVZooPlan::factory()->count(3)->yearly()->state(new Sequence(
        ['id' => 4441, 'plan_id' => $basic->id, 'metadata' => ['trial_credits' => 300, 'full_credits' => 1000]],
        ['id' => 4442, 'plan_id' => $standard->id, 'metadata' => ['trial_credits' => 300, 'full_credits' => 1000]],
        ['id' => 4443, 'plan_id' => $premium->id, 'metadata' => ['trial_credits' => 300, 'full_credits' => 1000]],
    ))->create();

    config(['services.jvzoo.plans' => [
        $yearly[0]->id => $yearly[0]->plan_id,
        $yearly[1]->id => $yearly[1]->plan_id,
        $yearly[2]->id => $yearly[2]->plan_id,
    ]]);

    $john = User::factory()->create([
        'name' => 'John Doe',
        'remaining_credit_amount' => 200,
        'monthly_credit_amount' => 500,
        'plan_id' => $yearly[0]->plan_id,
        'subscription_provider' => SubscriptionProvider::JVZOO->value,
    ]);

    $john->subscriptions()->create([
        'jvzoo_plan_id' => $yearly[0]->id,
        'status' => JVZooSubscription::STATUS_ACTIVE,
        'trial_ends_at' => now()->addMonth(),
        'receipt' => 'TEST123456',
    ]);

    CreditHistory::factory()->create([
        'user_id' => $john->id,
        'credit_events_id' => $subscriptionAmountPaid->id,
        'amount' => 500,
        'created_at' => now()->addMonth(),
        'updated_at' => now()->addMonth(),
    ]);

    // User 2: Started on Apr 10th with standard yearly plan
    $jane = User::factory()->create([
        'name' => 'Jane Doe',
        'remaining_credit_amount' => 800,
        'monthly_credit_amount' => 1000,
        'plan_id' => $yearly[1]->plan_id,
        'subscription_provider' => SubscriptionProvider::JVZOO->value,
    ]);

    $jane->subscriptions()->create([
        'jvzoo_plan_id' => $yearly[1]->id,
        'status' => JVZooSubscription::STATUS_ACTIVE,
        'trial_ends_at' => now()->addDays(10)->addMonths(3), // Jan 11th (will trigger on 11th of each month)
        'receipt' => 'TEST789012',
    ]);

    CreditHistory::factory()->create([
        'user_id' => $jane->id,
        'credit_events_id' => $subscriptionAmountPaid->id,
        'amount' => 1000,
        'created_at' => now()->addDays(10)->addMonths(3),
        'updated_at' => now()->addDays(10)->addMonths(3),
    ]);

    $this->artisan('syllaby:release-jvzoo-credits')->assertExitCode(0);

    $this->assertDatabaseHas('users', ['id' => $john->id, 'remaining_credit_amount' => 200]);
    $this->assertDatabaseHas('users', ['id' => $jane->id, 'remaining_credit_amount' => 800]);

    $this->travelTo(now()->addMonth()); // 2024-02-01
    $this->artisan('syllaby:release-jvzoo-credits')->assertExitCode(0);
    $this->assertDatabaseHas('users', ['id' => $john->id, 'remaining_credit_amount' => 200]);

    $this->travelTo(now()->addMonth(2)); // 2024-03-01
    $this->artisan('syllaby:release-jvzoo-credits')->assertExitCode(0);
    $this->assertDatabaseHas('users', ['id' => $john->id, 'remaining_credit_amount' => 700]);

    $this->travelTo(now()->addMonths(3)->addDays(10)); // 2024-04-11
    $this->artisan('syllaby:release-jvzoo-credits')->assertExitCode(0);
    $this->assertDatabaseHas('users', ['id' => $jane->id, 'remaining_credit_amount' => 1800]);
});
