<?php

namespace Tests\Feature\Subscriptions\JVZoo;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use App\Syllaby\Subscriptions\JVZooPlan;
use Database\Seeders\CreditEventTableSeeder;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Subscriptions\JVZooTransaction;
use App\Http\Middleware\JVZooSignatureValidator;
use App\Syllaby\Subscriptions\JVZooSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Subscriptions\Enums\JVZooTransactionType;
use App\Syllaby\Subscriptions\Enums\SubscriptionProvider;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CreditEventTableSeeder::class);
    $this->withoutMiddleware(JVZooSignatureValidator::class);
});

test('webhook rejects invalid transaction type', function () {
    $payload = payload(['transaction_type' => 'INVALID_TYPE']);

    $response = $this->postJson('/jvzoo/webhook', $payload);
    $response->assertStatus(400);
});

test('webhook handles duplicate transactions', function () {
    $payload = payload();

    JVZooTransaction::factory()->create([
        'receipt' => Arr::get($payload, 'ctransreceipt'),
        'transaction_type' => Arr::get($payload, 'ctransaction'),
    ]);

    $response = $this->postJson('/jvzoo/webhook', $payload);

    $response->assertStatus(200)->assertJson([
        'message' => 'Duplicate JVZoo transaction received',
    ]);
});

test('webhook creates transaction for new user sale', function () {
    Mail::fake();
    Event::fake([Registered::class]);

    $plan = Plan::factory()->create(['plan_id' => 'price_123']);
    config(['services.jvzoo.plans.123' => 'price_123']);

    $payload = payload([
        'product_id' => '123',
        'amount' => $plan->price,
        'customer_email' => 'newuser@example.com',
        'transaction_type' => JVZooTransactionType::SALE->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $payload);
    $response->assertStatus(200);

    $this->assertDatabaseHas('jvzoo_transactions', [
        'customer_email' => 'newuser@example.com',
        'transaction_type' => JVZooTransactionType::SALE->value,
        'receipt' => Arr::get($payload, 'ctransreceipt'),
    ]);

    $this->assertDatabaseHas('users', [
        'plan_id' => $plan->id,
        'email' => 'newuser@example.com',
        'subscription_provider' => SubscriptionProvider::JVZOO,
    ]);

    $this->assertDatabaseHas('jvzoo_plans', [
        'plan_id' => $plan->id,
        'jvzoo_id' => Arr::get($payload, 'cproditem'),
    ]);
});

test('webhook handles existing user cancellation', function () {
    $user = User::factory()->create([
        'subscription_provider' => SubscriptionProvider::JVZOO,
    ]);

    $plan = Plan::factory()->create();
    $jvzooPlan = JVZooPlan::factory()->create([
        'plan_id' => $plan->id,
        'jvzoo_id' => '418754',
    ]);

    config(['services.jvzoo.plans.418754' => $plan->plan_id]);

    $subscription = JVZooSubscription::factory()->create([
        'user_id' => $user->id,
        'jvzoo_plan_id' => $jvzooPlan->id,
        'status' => JVZooSubscription::STATUS_ACTIVE,
    ]);

    $payload = payload([
        'product_id' => '418754',
        'customer_email' => $user->email,
        'transaction_type' => JVZooTransactionType::CANCEL_REBILL->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $payload);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Success']);

    $this->assertDatabaseHas('jvzoo_transactions', [
        'user_id' => $user->id,
        'jvzoo_subscription_id' => $subscription->id,
        'receipt' => Arr::get($payload, 'ctransreceipt'),
        'transaction_type' => JVZooTransactionType::CANCEL_REBILL->value,
    ]);

    $subscription->refresh();
    expect($subscription->status)->toBe(JVZooSubscription::STATUS_CANCELED);
    expect($subscription->ends_at)->not->toBeNull();
});

test('webhook handles payment failure', function () {
    $user = User::factory()->create([
        'subscription_provider' => SubscriptionProvider::JVZOO,
    ]);

    $plan = Plan::factory()->create();
    $jvzooPlan = JVZooPlan::factory()->create([
        'plan_id' => $plan->id,
        'jvzoo_id' => '418754',
    ]);

    config(['services.jvzoo.plans.418754' => $plan->plan_id]);

    $subscription = JVZooSubscription::factory()->create([
        'user_id' => $user->id,
        'jvzoo_plan_id' => $jvzooPlan->id,
        'status' => JVZooSubscription::STATUS_ACTIVE,
    ]);

    $payload = payload([
        'product_id' => '418754',
        'customer_email' => $user->email,
        'transaction_type' => JVZooTransactionType::INSF->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $payload);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Success']);

    $subscription->refresh();
    expect($subscription->status)->toBe(JVZooSubscription::STATUS_EXPIRED);
});

test('webhook handles payment recovery after failure', function () {
    $user = User::factory()->create([
        'subscription_provider' => SubscriptionProvider::JVZOO,
    ]);

    $plan = Plan::factory()->create();
    $jvzooPlan = JVZooPlan::factory()->create(['plan_id' => $plan->id, 'jvzoo_id' => '418754']);
    config(['services.jvzoo.plans.418754' => $plan->plan_id]);

    $subscription = JVZooSubscription::factory()->create([
        'user_id' => $user->id,
        'jvzoo_plan_id' => $jvzooPlan->id,
        'status' => JVZooSubscription::STATUS_EXPIRED,
    ]);

    $payload = payload([
        'product_id' => '418754',
        'customer_email' => $user->email,
        'transaction_type' => JVZooTransactionType::BILL->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $payload);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Success']);

    $subscription->refresh();
    expect($subscription->status)->toBe(JVZooSubscription::STATUS_ACTIVE);
});

test('webhook records transaction even when user not found', function () {
    $plan = Plan::factory()->create();

    JVZooPlan::factory()->create(['plan_id' => $plan->id, 'jvzoo_id' => '418754']);
    config(['services.jvzoo.plans.418754' => $plan->plan_id]);

    $payload = payload([
        'product_id' => '418754',
        'customer_email' => 'nonexistent@example.com',
        'transaction_type' => JVZooTransactionType::CANCEL_REBILL->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $payload);

    $response->assertStatus(200)
        ->assertJson(['message' => 'User not found']);

    $this->assertDatabaseHas('jvzoo_transactions', [
        'user_id' => null,
        'customer_email' => 'nonexistent@example.com',
        'receipt' => Arr::get($payload, 'ctransreceipt'),
        'transaction_type' => JVZooTransactionType::CANCEL_REBILL->value,
    ]);
});

test('webhook allows different transaction types with same receipt', function () {
    $user = User::factory()->create([
        'subscription_provider' => SubscriptionProvider::JVZOO,
    ]);

    $plan = Plan::factory()->create();
    JVZooPlan::factory()->create(['plan_id' => $plan->id, 'jvzoo_id' => '418754']);
    config(['services.jvzoo.plans.418754' => $plan->plan_id]);

    $receipt = 'TEST123456';

    $salePayload = payload([
        'product_id' => '418754',
        'customer_email' => $user->email,
        'receipt' => $receipt,
        'transaction_type' => JVZooTransactionType::SALE->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $salePayload);
    $response->assertStatus(200);

    $cancelPayload = payload([
        'product_id' => '418754',
        'customer_email' => $user->email,
        'receipt' => $receipt,
        'transaction_type' => JVZooTransactionType::CANCEL_REBILL->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $cancelPayload);
    $response->assertStatus(200);

    expect(JVZooTransaction::where('receipt', $receipt)->count())->toBe(2);

    $this->assertDatabaseHas('jvzoo_transactions', [
        'customer_email' => $user->email,
        'receipt' => $receipt,
        'transaction_type' => JVZooTransactionType::SALE->value,
    ]);

    $this->assertDatabaseHas('jvzoo_transactions', [
        'customer_email' => $user->email,
        'receipt' => $receipt,
        'transaction_type' => JVZooTransactionType::CANCEL_REBILL->value,
    ]);
});

test('webhook handles trial to paid subscription with same receipt', function () {
    Mail::fake();
    Event::fake([Registered::class]);

    $plan = Plan::factory()->create(['price' => 2997, 'name' => 'Pro Plan', 'type' => 'monthly']);
    config(['services.jvzoo.plans.123-pro' => $plan->plan_id]);

    $receipt = 'TRIAL-TO-PAID-123456';

    // First, process trial transaction ($0 SALE)
    $trialPayload = payload([
        'amount' => 0,
        'product_id' => '123-pro',
        'receipt' => $receipt,
        'product_name' => 'Pro Plan - Trial',
        'customer_email' => 'trial@example.com',
        'transaction_type' => JVZooTransactionType::SALE->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $trialPayload);
    $response->assertStatus(200);

    $user = User::where('email', 'trial@example.com')->first();
    expect($user->plan_id)->toBe($plan->id);

    $jvzooPlan = JVZooPlan::where('jvzoo_id', '123-pro')->first();
    $subscription = JVZooSubscription::where('user_id', $user->id)->first();
    expect($subscription->jvzoo_plan_id)->toBe($jvzooPlan->id);
    expect($subscription->status)->toBe(JVZooSubscription::STATUS_TRIAL);

    // Then, process paid subscription with same receipt (BILL transaction)
    $paidPayload = payload([
        'product_id' => '123-pro',
        'receipt' => $receipt,
        'transaction_type' => JVZooTransactionType::BILL->value,
        'amount' => 2997,
        'product_name' => 'Pro Plan',
        'customer_email' => 'trial@example.com',
    ]);

    $response = $this->postJson('/jvzoo/webhook', $paidPayload);
    $response->assertStatus(200);

    $user->refresh();
    expect($user->plan_id)->toBe($plan->id);

    $subscription->refresh();
    expect($subscription->jvzoo_plan_id)->toBe($jvzooPlan->id);
    expect($subscription->status)->toBe(JVZooSubscription::STATUS_ACTIVE);

    expect(JVZooTransaction::where('receipt', $receipt)->count())->toBe(2);
});

test('webhook adds trial credits when user starts trial', function () {
    Mail::fake();
    Event::fake([Registered::class]);

    $plan = Plan::factory()->price()->create([
        'price' => 2997,
        'name' => 'Pro Plan Trial',
        'type' => 'monthly',
        'meta' => ['trial_credits' => 50, 'full_credits' => 500],
    ]);
    JVZooPlan::factory()->create([
        'plan_id' => $plan->id,
        'jvzoo_id' => 'trial-credits-123',
        'metadata' => ['trial_credits' => 50, 'full_credits' => 500],
    ]);
    config(['services.jvzoo.plans.trial-credits-123' => $plan->plan_id]);

    $trialPayload = payload([
        'amount' => 0,
        'product_id' => 'trial-credits-123',
        'product_name' => 'Pro Plan - Trial',
        'customer_email' => 'newtrialuser@example.com',
        'transaction_type' => JVZooTransactionType::SALE->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $trialPayload);
    $response->assertStatus(200);

    $user = User::where('email', 'newtrialuser@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->plan_id)->toBe($plan->id);
    expect($user->plan->type)->toBe('monthly');
    expect($user->monthly_credit_amount)->toBe(50);
    expect($user->remaining_credit_amount)->toBe(50);

    $subscription = JVZooSubscription::where('user_id', $user->id)->first();
    expect($subscription->status)->toBe(JVZooSubscription::STATUS_TRIAL);

    $this->assertDatabaseHas('credit_histories', [
        'user_id' => $user->id,
        'description' => CreditEventEnum::SUBSCRIBE_TO_TRIAL->value,
    ]);
});

test('webhook handles plan upgrade with same receipt', function () {
    $basicPlan = Plan::factory()->create(['price' => 1997, 'name' => 'Basic Plan']);
    $proPlan = Plan::factory()->create(['price' => 4997, 'name' => 'Pro Plan']);

    $basicJVZooPlan = JVZooPlan::factory()->create([
        'plan_id' => $basicPlan->id,
        'jvzoo_id' => 'basic-123',
    ]);

    $proJVZooPlan = JVZooPlan::factory()->create([
        'plan_id' => $proPlan->id,
        'jvzoo_id' => 'pro-123',
    ]);

    config(['services.jvzoo.plans.basic-123' => $basicPlan->plan_id]);
    config(['services.jvzoo.plans.pro-123' => $proPlan->plan_id]);

    $user = User::factory()->create([
        'plan_id' => $basicPlan->id,
        'subscription_provider' => SubscriptionProvider::JVZOO,
    ]);

    $subscription = JVZooSubscription::factory()->create([
        'user_id' => $user->id,
        'receipt' => 'BASIC-RECEIPT-123',
        'jvzoo_plan_id' => $basicJVZooPlan->id,
        'status' => JVZooSubscription::STATUS_ACTIVE,
    ]);

    $refundPayload = payload([
        'amount' => 1997,
        'product_id' => 'basic-123',
        'customer_email' => $user->email,
        'receipt' => 'BASIC-RECEIPT-123',
        'transaction_type' => JVZooTransactionType::RFND->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $refundPayload);
    $response->assertStatus(200);

    $salePayload = payload([
        'product_id' => 'pro-123',
        'receipt' => 'PRO-RECEIPT-456',
        'transaction_type' => JVZooTransactionType::SALE->value,
        'amount' => 4997,
        'customer_email' => $user->email,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $salePayload);
    $response->assertStatus(200);

    $user->refresh();
    expect($user->plan_id)->toBe($proPlan->id);

    $subscription->refresh();
    expect($subscription->status)->toBe(JVZooSubscription::STATUS_CANCELED);

    $newSubscription = JVZooSubscription::where('user_id', $user->id)
        ->where('status', JVZooSubscription::STATUS_ACTIVE)
        ->orderBy('created_at', 'desc')
        ->first();

    expect($newSubscription)->not->toBeNull();
    expect($newSubscription->id)->not->toBe($subscription->id);
    expect($newSubscription->jvzoo_plan_id)->toBe($proJVZooPlan->id);
    expect($newSubscription->status)->toBe(JVZooSubscription::STATUS_ACTIVE);

    expect(JVZooTransaction::where('customer_email', $user->email)->count())->toBe(2);
})->skip('Not yet decided how to handle this');

test('webhook handles plan downgrade with same receipt', function () {
    $proPlan = Plan::factory()->create(['price' => 4997, 'name' => 'Pro Plan']);
    $basicPlan = Plan::factory()->create(['price' => 1997, 'name' => 'Basic Plan']);

    $proJVZooPlan = JVZooPlan::factory()->create([
        'plan_id' => $proPlan->id,
        'jvzoo_id' => 'pro-456',
    ]);

    $basicJVZooPlan = JVZooPlan::factory()->create([
        'plan_id' => $basicPlan->id,
        'jvzoo_id' => 'basic-456',
    ]);

    config(['services.jvzoo.plans.pro-456' => $proPlan->plan_id]);
    config(['services.jvzoo.plans.basic-456' => $basicPlan->plan_id]);

    $user = User::factory()->create([
        'plan_id' => $proPlan->id,
        'subscription_provider' => SubscriptionProvider::JVZOO,
    ]);

    $subscription = JVZooSubscription::factory()->create([
        'user_id' => $user->id,
        'jvzoo_plan_id' => $proJVZooPlan->id,
        'status' => JVZooSubscription::STATUS_ACTIVE,
        'receipt' => 'PRO-RECEIPT-789',
    ]);

    $refundPayload = payload([
        'product_id' => 'pro-456',
        'receipt' => 'PRO-RECEIPT-789',
        'transaction_type' => JVZooTransactionType::RFND->value,
        'amount' => 4997,
        'customer_email' => $user->email,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $refundPayload);
    $response->assertStatus(200);

    $salePayload = payload([
        'product_id' => 'basic-456',
        'receipt' => 'BASIC-RECEIPT-012',
        'transaction_type' => JVZooTransactionType::SALE->value,
        'amount' => 1997,
        'customer_email' => $user->email,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $salePayload);
    $response->assertStatus(200);

    $user->refresh();
    expect($user->plan_id)->toBe($basicPlan->id);

    $subscription->refresh();
    expect($subscription->status)->toBe(JVZooSubscription::STATUS_CANCELED);

    $newSubscription = JVZooSubscription::where('user_id', $user->id)
        ->where('status', JVZooSubscription::STATUS_ACTIVE)
        ->orderBy('created_at', 'desc')
        ->first();

    expect($newSubscription)->not->toBeNull();
    expect($newSubscription->id)->not->toBe($subscription->id);
    expect($newSubscription->jvzoo_plan_id)->toBe($basicJVZooPlan->id);
    expect($newSubscription->status)->toBe(JVZooSubscription::STATUS_ACTIVE);

    expect(JVZooTransaction::where('customer_email', $user->email)->count())->toBe(2);
})->skip('Not yet decided how to handle this');

test('webhook handles full refund', function () {
    $plan = Plan::factory()->create(['price' => 2997, 'type' => 'monthly']);

    $jvzooPlan = JVZooPlan::factory()->create([
        'plan_id' => $plan->id,
        'jvzoo_id' => 'refund-123',
    ]);

    config(['services.jvzoo.plans.refund-123' => $plan->plan_id]);

    $user = User::factory()->create([
        'plan_id' => $plan->id,
        'subscription_provider' => SubscriptionProvider::JVZOO,
    ]);

    $subscription = JVZooSubscription::factory()->create([
        'user_id' => $user->id,
        'jvzoo_plan_id' => $jvzooPlan->id,
        'status' => JVZooSubscription::STATUS_ACTIVE,
    ]);

    $payload = payload([
        'product_id' => 'refund-123',
        'amount' => 2997,
        'customer_email' => $user->email,
        'transaction_type' => JVZooTransactionType::RFND->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $payload);
    $response->assertStatus(200);

    $subscription->refresh();
    expect($subscription->status)->toBe(JVZooSubscription::STATUS_CANCELED);
    expect($subscription->ends_at)->not->toBeNull();
});

test('webhook handles partial refund', function () {
    $plan = Plan::factory()->create(['price' => 4997, 'type' => 'monthly']);

    $jvzooPlan = JVZooPlan::factory()->create([
        'plan_id' => $plan->id,
        'jvzoo_id' => 'partial-refund-123',
    ]);

    config(['services.jvzoo.plans.partial-refund-123' => $plan->plan_id]);

    $user = User::factory()->create([
        'plan_id' => $plan->id,
        'subscription_provider' => SubscriptionProvider::JVZOO,
    ]);

    $subscription = JVZooSubscription::factory()->create([
        'user_id' => $user->id,
        'jvzoo_plan_id' => $jvzooPlan->id,
        'status' => JVZooSubscription::STATUS_ACTIVE,
    ]);

    $payload = payload([
        'amount' => 1000,
        'customer_email' => $user->email,
        'product_id' => 'partial-refund-123',
        'transaction_type' => JVZooTransactionType::RFND->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $payload);
    $response->assertStatus(200);

    $subscription->refresh();
    expect($subscription->status)->toBe(JVZooSubscription::STATUS_CANCELED);
    expect($subscription->ends_at)->not->toBeNull();

    $this->assertDatabaseHas('jvzoo_transactions', [
        'user_id' => $user->id,
        'amount' => 1000,
        'transaction_type' => JVZooTransactionType::RFND->value,
    ]);
});

test('webhook handles chargeback', function () {
    $plan = Plan::factory()->create(['price' => 2997, 'type' => 'monthly']);

    $jvzooPlan = JVZooPlan::factory()->create([
        'plan_id' => $plan->id,
        'jvzoo_id' => 'chargeback-123',
    ]);

    config(['services.jvzoo.plans.chargeback-123' => $plan->plan_id]);

    $user = User::factory()->create([
        'plan_id' => $plan->id,
        'subscription_provider' => SubscriptionProvider::JVZOO,
    ]);

    $subscription = JVZooSubscription::factory()->create([
        'user_id' => $user->id,
        'jvzoo_plan_id' => $jvzooPlan->id,
        'status' => JVZooSubscription::STATUS_ACTIVE,
    ]);

    $payload = payload([
        'product_id' => 'chargeback-123',
        'transaction_type' => JVZooTransactionType::CGBK->value,
        'amount' => 2997,
        'customer_email' => $user->email,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $payload);
    $response->assertStatus(200);

    $subscription->refresh();
    expect($subscription->status)->toBe(JVZooSubscription::STATUS_CANCELED);

    $this->assertDatabaseHas('jvzoo_transactions', [
        'transaction_type' => JVZooTransactionType::CGBK->value,
        'user_id' => $user->id,
    ]);
});

test('webhook handles subscription renewal', function () {
    $plan = Plan::factory()->create(['price' => 2997, 'type' => 'monthly']);
    $jvzooPlan = JVZooPlan::factory()->create(['plan_id' => $plan->id, 'jvzoo_id' => 'renewal-123']);
    config(['services.jvzoo.plans.renewal-123' => $plan->plan_id]);

    $user = User::factory()->create([
        'plan_id' => $plan->id,
        'subscription_provider' => SubscriptionProvider::JVZOO,
    ]);

    $subscription = JVZooSubscription::factory()->create([
        'ends_at' => null,
        'user_id' => $user->id,
        'jvzoo_plan_id' => $jvzooPlan->id,
        'status' => JVZooSubscription::STATUS_ACTIVE,
    ]);

    $payload = payload([
        'amount' => 2997,
        'product_id' => 'renewal-123',
        'customer_email' => $user->email,
        'transaction_type' => JVZooTransactionType::BILL->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $payload);
    $response->assertStatus(200);

    $subscription->refresh();
    expect($subscription->status)->toBe(JVZooSubscription::STATUS_ACTIVE);
    expect($subscription->ends_at)->toBeNull();

    $this->assertDatabaseHas('jvzoo_transactions', [
        'user_id' => $user->id,
        'jvzoo_subscription_id' => $subscription->id,
        'transaction_type' => JVZooTransactionType::BILL->value,
    ]);
});

test('webhook renews account credits on subscription renewal', function () {
    $plan = Plan::factory()->price()->create([
        'price' => 2997,
        'type' => 'monthly',
        'meta' => ['trial_credits' => 50, 'full_credits' => 2000],
    ]);
    $jvzooPlan = JVZooPlan::factory()->create([
        'plan_id' => $plan->id,
        'jvzoo_id' => 'renewal-credits-123',
        'metadata' => ['trial_credits' => 50, 'full_credits' => 2000],
    ]);
    config(['services.jvzoo.plans.renewal-credits-123' => $plan->plan_id]);

    $user = User::factory()->create([
        'plan_id' => $plan->id,
        'subscription_provider' => SubscriptionProvider::JVZOO,
        'monthly_credit_amount' => 2000,
        'remaining_credit_amount' => 100,
    ]);

    $subscription = JVZooSubscription::factory()->create([
        'ends_at' => null,
        'user_id' => $user->id,
        'jvzoo_plan_id' => $jvzooPlan->id,
        'status' => JVZooSubscription::STATUS_ACTIVE,
    ]);

    $payload = payload([
        'amount' => 2997,
        'product_id' => 'renewal-credits-123',
        'customer_email' => $user->email,
        'transaction_type' => JVZooTransactionType::BILL->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $payload);
    $response->assertStatus(200);

    $user->refresh();
    expect($user->monthly_credit_amount)->toBe(2000);
    expect($user->remaining_credit_amount)->toBe(2000);

    $this->assertDatabaseHas('credit_histories', [
        'user_id' => $user->id,
        'description' => CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID->value,
    ]);
});

test('webhook handles multiple payment attempts', function () {
    $plan = Plan::factory()->create(['price' => 2997, 'type' => 'monthly']);

    $jvzooPlan = JVZooPlan::factory()->create([
        'plan_id' => $plan->id,
        'jvzoo_id' => 'retry-123',
    ]);

    config(['services.jvzoo.plans.retry-123' => $plan->plan_id]);

    $user = User::factory()->create([
        'plan_id' => $plan->id,
        'subscription_provider' => SubscriptionProvider::JVZOO,
    ]);

    $subscription = JVZooSubscription::factory()->create([
        'user_id' => $user->id,
        'jvzoo_plan_id' => $jvzooPlan->id,
        'status' => JVZooSubscription::STATUS_ACTIVE,
    ]);

    $receipt = 'RETRY-PAYMENT-123';

    $failedPayload = payload([
        'product_id' => 'retry-123',
        'receipt' => $receipt,
        'amount' => 2997,
        'customer_email' => $user->email,
        'transaction_type' => JVZooTransactionType::INSF->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $failedPayload);
    $response->assertStatus(200);

    $subscription->refresh();
    expect($subscription->status)->toBe(JVZooSubscription::STATUS_EXPIRED);

    $successPayload = payload([
        'product_id' => 'retry-123',
        'receipt' => $receipt,
        'transaction_type' => JVZooTransactionType::BILL->value,
        'amount' => 2997,
        'customer_email' => $user->email,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $successPayload);
    $response->assertStatus(200);

    $subscription->refresh();
    expect($subscription->status)->toBe(JVZooSubscription::STATUS_ACTIVE);

    expect(JVZooTransaction::where('receipt', $receipt)->count())->toBe(2);
});

test('webhook handles orphaned transactions', function () {
    $plan = Plan::factory()->create(['type' => 'monthly']);
    JVZooPlan::factory()->create([
        'plan_id' => $plan->id,
        'jvzoo_id' => 'orphan-123',
    ]);

    config(['services.jvzoo.plans.orphan-123' => $plan->plan_id]);

    // Transaction for non-existent subscription
    $payload = payload([
        'product_id' => 'orphan-123',
        'transaction_type' => JVZooTransactionType::BILL->value,
        'amount' => 2997,
        'customer_email' => 'orphan@example.com',
    ]);

    $response = $this->postJson('/jvzoo/webhook', $payload);
    $response->assertStatus(200);

    $this->assertDatabaseHas('jvzoo_transactions', [
        'transaction_type' => JVZooTransactionType::BILL->value,
        'customer_email' => 'orphan@example.com',
        'user_id' => null,
        'jvzoo_subscription_id' => null,
    ]);
});

test('webhook handles out of order transactions', function () {
    Mail::fake();
    Event::fake([Registered::class]);

    $plan = Plan::factory()->create(['price' => 2997, 'type' => 'monthly']);
    config(['services.jvzoo.plans.out-of-order-123' => $plan->plan_id]);

    $receipt = 'OUT-OF-ORDER-123';
    $email = 'outoforder@example.com';

    $billPayload = payload([
        'customer_email' => $email,
        'amount' => 2997,
        'receipt' => $receipt,
        'product_id' => 'out-of-order-123',
        'transaction_type' => JVZooTransactionType::BILL->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $billPayload);
    $response->assertStatus(200);

    $this->assertDatabaseHas('jvzoo_transactions', [
        'user_id' => null,
        'customer_email' => $email,
        'receipt' => $receipt,
        'transaction_type' => JVZooTransactionType::BILL->value,
    ]);

    expect(User::where('email', $email)->exists())->toBe(false);

    $salePayload = payload([
        'customer_email' => $email,
        'amount' => 2997,
        'receipt' => $receipt,
        'product_id' => 'out-of-order-123',
        'transaction_type' => JVZooTransactionType::SALE->value,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $salePayload);
    $response->assertStatus(200);

    $user = User::where('email', $email)->first();
    expect($user)->not->toBeNull();
    expect($user->plan_id)->toBe($plan->id);

    expect(JVZooTransaction::where('receipt', $receipt)->count())->toBe(2);

    $this->assertDatabaseHas('jvzoo_subscriptions', [
        'user_id' => $user->id,
        'status' => JVZooSubscription::STATUS_ACTIVE,
    ]);
});

test('user can only have one active subscription at a time', function () {
    $plan = Plan::factory()->create(['price' => 2997, 'type' => 'monthly']);
    $jvzooPlan = JVZooPlan::factory()->create([
        'plan_id' => $plan->id,
        'jvzoo_id' => 'single-active-123',
    ]);
    config(['services.jvzoo.plans.single-active-123' => $plan->plan_id]);

    $user = User::factory()->create([
        'plan_id' => $plan->id,
        'subscription_provider' => SubscriptionProvider::JVZOO,
    ]);

    $subscription = JVZooSubscription::factory()->create([
        'user_id' => $user->id,
        'jvzoo_plan_id' => $jvzooPlan->id,
        'status' => JVZooSubscription::STATUS_ACTIVE,
    ]);

    $payload = payload([
        'product_id' => 'single-active-123',
        'customer_email' => $user->email,
        'transaction_type' => JVZooTransactionType::SALE->value,
        'amount' => 2997,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $payload);
    $response->assertStatus(200);

    expect($user->subscriptions()->where('status', JVZooSubscription::STATUS_ACTIVE)->count())->toBe(1);
    expect($subscription->fresh()->status)->toBe(JVZooSubscription::STATUS_ACTIVE);
});

test('user can only have one trial subscription at a time', function () {
    $plan = Plan::factory()->create(['price' => 2997, 'type' => 'monthly']);
    $jvzooPlan = JVZooPlan::factory()->create([
        'plan_id' => $plan->id,
        'jvzoo_id' => 'single-trial-123',
    ]);
    config(['services.jvzoo.plans.single-trial-123' => $plan->plan_id]);

    $user = User::factory()->create([
        'plan_id' => $plan->id,
        'subscription_provider' => SubscriptionProvider::JVZOO,
    ]);

    $trialSubscription = JVZooSubscription::factory()->create([
        'user_id' => $user->id,
        'jvzoo_plan_id' => $jvzooPlan->id,
        'status' => JVZooSubscription::STATUS_TRIAL,
    ]);

    $salePayload = payload([
        'product_id' => 'single-trial-123',
        'customer_email' => $user->email,
        'transaction_type' => JVZooTransactionType::SALE->value,
        'amount' => 0,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $salePayload);
    $response->assertStatus(200);

    expect(JVZooSubscription::where('user_id', $user->id)
        ->where('status', JVZooSubscription::STATUS_TRIAL)
        ->count())->toBe(1);

    expect($trialSubscription->fresh()->status)->toBe(JVZooSubscription::STATUS_TRIAL);
});

test('user can only have one suspended subscription at a time', function () {
    $plan = Plan::factory()->create(['price' => 2997, 'type' => 'monthly']);
    $jvzooPlan = JVZooPlan::factory()->create([
        'plan_id' => $plan->id,
        'jvzoo_id' => 'single-expired-123',
    ]);
    config(['services.jvzoo.plans.single-expired-123' => $plan->plan_id]);

    $user = User::factory()->create([
        'plan_id' => $plan->id,
        'subscription_provider' => SubscriptionProvider::JVZOO,
    ]);

    $expiredSubscription = JVZooSubscription::factory()->create([
        'user_id' => $user->id,
        'jvzoo_plan_id' => $jvzooPlan->id,
        'status' => JVZooSubscription::STATUS_EXPIRED,
    ]);

    $salePayload = payload([
        'product_id' => 'single-expired-123',
        'customer_email' => $user->email,
        'transaction_type' => JVZooTransactionType::SALE->value,
        'amount' => 2997,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $salePayload);
    $response->assertStatus(200);

    expect(JVZooSubscription::where('user_id', $user->id)
        ->where('status', JVZooSubscription::STATUS_EXPIRED)
        ->count())->toBe(1);

    expect($expiredSubscription->fresh()->status)->toBe(JVZooSubscription::STATUS_EXPIRED);
});

test('user can create new subscription if previous subscription is canceled', function () {
    $plan = Plan::factory()->create(['price' => 2997, 'type' => 'monthly']);
    $jvzooPlan = JVZooPlan::factory()->create([
        'plan_id' => $plan->id,
        'jvzoo_id' => 'new-after-cancel-123',
    ]);
    config(['services.jvzoo.plans.new-after-cancel-123' => $plan->plan_id]);

    $user = User::factory()->create([
        'plan_id' => $plan->id,
        'subscription_provider' => SubscriptionProvider::JVZOO,
    ]);

    $canceledSubscription = JVZooSubscription::factory()->create([
        'user_id' => $user->id,
        'jvzoo_plan_id' => $jvzooPlan->id,
        'status' => JVZooSubscription::STATUS_CANCELED,
        'ends_at' => now()->subDay(),
    ]);

    $salePayload = payload([
        'product_id' => 'new-after-cancel-123',
        'customer_email' => $user->email,
        'transaction_type' => JVZooTransactionType::SALE->value,
        'amount' => 2997,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $salePayload);
    $response->assertStatus(200);

    $subscriptions = JVZooSubscription::where('user_id', $user->id)->get();
    expect($subscriptions)->toHaveCount(2);

    $activeSubscription = $subscriptions->where('status', JVZooSubscription::STATUS_ACTIVE)->first();
    expect($activeSubscription)->not->toBeNull();
    expect($activeSubscription->id)->not->toBe($canceledSubscription->id);

    expect($canceledSubscription->fresh()->status)->toBe(JVZooSubscription::STATUS_CANCELED);
});

test('user can create new subscription if no previous subscription exists', function () {
    Mail::fake();
    Event::fake([Registered::class]);

    $plan = Plan::factory()->create(['price' => 2997, 'type' => 'monthly']);
    config(['services.jvzoo.plans.first-sub-123' => $plan->plan_id]);

    $salePayload = payload([
        'product_id' => 'first-sub-123',
        'customer_email' => 'firstsub@example.com',
        'transaction_type' => JVZooTransactionType::SALE->value,
        'amount' => 2997,
    ]);

    $response = $this->postJson('/jvzoo/webhook', $salePayload);
    $response->assertStatus(200);

    $user = User::where('email', 'firstsub@example.com')->first();
    expect($user)->not->toBeNull();

    $subscription = JVZooSubscription::where('user_id', $user->id)->first();
    expect($subscription)->not->toBeNull();
    expect($subscription->status)->toBe(JVZooSubscription::STATUS_ACTIVE);
});

function payload(array $overrides = []): array
{
    $attributes = JVZooTransaction::factory()->make($overrides)->toArray();

    return [
        'ctransreceipt' => Arr::get($attributes, 'receipt'),
        'cproditem' => Arr::get($attributes, 'product_id'),
        'cprodtitle' => Arr::get($attributes, 'product_name'),
        'cprodtype' => Arr::get($attributes, 'product_type'),
        'ctransaction' => Arr::get($attributes, 'transaction_type'),
        'ctransamount' => number_format(Arr::get($attributes, 'amount') / 100, 2, '.', ''),
        'ctranspaymentmethod' => Arr::get($attributes, 'payment_method'),
        'ctransvendor' => Arr::get($attributes, 'vendor'),
        'ctransaffiliate' => Arr::get($attributes, 'affiliate'),
        'ctranstime' => now()->timestamp,
        'ccustemail' => Arr::get($attributes, 'customer_email'),
        'ccustname' => Arr::get($attributes, 'customer_name'),
        'ccuststate' => Arr::get($attributes, 'customer_state'),
        'ccustcc' => Arr::get($attributes, 'customer_country'),
        'cupsellreceipt' => Arr::get($attributes, 'upsell_receipt'),
        'caffitid' => Arr::get($attributes, 'affiliate_tracking_id'),
        'cvendthru' => Arr::get($attributes, 'vendor_through'),
        'cverify' => Arr::get($attributes, 'verification_hash'),
    ];
}
