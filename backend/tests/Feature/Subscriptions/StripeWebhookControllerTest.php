<?php

namespace Tests\Feature\Subscriptions;

use Carbon\Carbon;
use Mockery\MockInterface;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Shared\TikTok\TikTok;
use App\Shared\Facebook\Pixel;
use App\Syllaby\Clonables\Clonable;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Events as Hooks;
use App\Syllaby\Subscriptions\Events;
use Illuminate\Support\Facades\Event;
use Database\Seeders\CreditEventTableSeeder;
use Illuminate\Support\Facades\Notification;
use Stripe\Subscription as StripeSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Subscriptions\Actions\ExtendTrialAction;
use App\Syllaby\Clonables\Mails\UserRequestedAvatarClone;
use App\Syllaby\Subscriptions\Notifications\TrialExtended;
use App\Syllaby\Subscriptions\Listeners\GenerateWelcomeEmail;
use App\Syllaby\Subscriptions\Notifications\InvoicePaymentFailed;
use App\Syllaby\Subscriptions\Notifications\TrialWillEndReminder;
use App\Syllaby\Subscriptions\Listeners\TrackTrialStartedListener;
use App\Syllaby\Subscriptions\Notifications\SubscriptionTermination;
use App\Syllaby\Clonables\Notifications\AvatarCloneCheckoutCompleted;
use App\Syllaby\Subscriptions\Notifications\SubscriptionCancellation;
use App\Syllaby\Subscriptions\Notifications\SubscriptionConfirmation;
use App\Syllaby\Subscriptions\Listeners\TrackSubscriptionStartedListener;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CreditEventTableSeeder::class);
});

test('checkout.session.completed - it sets purchased extra credits to user', function () {
    Notification::fake();

    Event::fake()->except([
        Hooks\WebhookReceived::class,
        Events\CheckoutCompleted::class,
    ]);

    $item = Plan::factory()->price()->create([
        'type' => 'one_time',
        'meta' => ['credits' => 10000, 'type' => 'extra-credits'],
    ]);

    $plan = Plan::factory()->price()->create();
    $user = User::factory()->withSubscription($plan)->create([
        'stripe_id' => 'cus_foo-bar-baz',
    ]);

    $response = $this->postJson('stripe/webhook', [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'mode' => 'payment',
                'object' => 'checkout.session',
                'customer' => 'cus_foo-bar-baz',
                'payment_intent' => 'int_999999',
                'customer_email' => $user->email,
                'client_reference_id' => $user->id,
                'metadata' => [
                    'action' => $item->meta['type'],
                    'price_id' => $item->plan_id,
                ],
            ],
        ],
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'extra_credits' => 10000,
        'stripe_id' => 'cus_foo-bar-baz',
    ]);

    $this->assertDatabaseHas('purchases', [
        'user_id' => $user->id,
        'plan_id' => $item->id,
        'payment_intent' => 'int_999999',
    ]);
});

test('checkout.session.completed - it allows user to purchase a real clone avatar', function () {
    Mail::fake();
    Notification::fake();

    Event::fake()->except([
        Hooks\WebhookReceived::class,
        Events\CheckoutCompleted::class,
    ]);

    $item = Plan::factory()->price()->create([
        'name' => 'Real Clone Studio',
        'type' => 'one_time',
        'meta' => ['type' => 'real-clone-avatar'],
    ]);

    $plan = Plan::factory()->price()->create();
    $user = User::factory()->withSubscription($plan)->create([
        'stripe_id' => 'cus_foo-bar-baz',
    ]);

    $clonable = Clonable::factory()->create([
        'purchase_id' => null,
        'user_id' => $user->id,
        'model_type' => 'avatar',
    ]);

    $response = $this->postJson('stripe/webhook', [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'mode' => 'payment',
                'object' => 'checkout.session',
                'customer' => 'cus_foo-bar-baz',
                'payment_intent' => 'int_999999',
                'customer_email' => $user->email,
                'client_reference_id' => $user->id,
                'metadata' => [
                    'action' => $item->meta['type'],
                    'price_id' => $item->plan_id,
                    'clonable_id' => $clonable->id,
                ],
            ],
        ],
    ]);

    $response->assertOk();
    Mail::assertSent(UserRequestedAvatarClone::class);
    Notification::assertSentTo($user, AvatarCloneCheckoutCompleted::class);

    $this->assertDatabaseHas('purchases', [
        'user_id' => $user->id,
        'plan_id' => $item->id,
        'payment_intent' => 'int_999999',
    ]);

    expect($clonable->fresh()->purchase_id)->not->toBeNull();
});

test('invoice.payment_succeeded - it renew account credits for billing cycle', function () {
    Notification::fake();

    Event::fake()->except([
        Hooks\WebhookReceived::class,
        Events\PaymentSucceeded::class,
    ]);

    $plan = Plan::factory()->price()->create();
    $user = User::factory()->withSubscription($plan)->create([
        'stripe_id' => 'cus_foo-bar-baz',
        'remaining_credit_amount' => 10,
        'monthly_credit_amount' => 2000,
    ]);

    $subscription = $user->subscription();

    $response = $this->postJson('stripe/webhook', [
        'type' => 'invoice.payment_succeeded',
        'data' => [
            'object' => [
                'object' => 'invoice',
                'customer' => $user->stripe_id,
                'customer_email' => $user->email,
                'billing_reason' => 'subscription_cycle',
                'lines' => [
                    'data' => [[
                        'price' => [
                            'id' => $subscription->stripe_price,
                            'recurring' => ['interval' => 'month'],
                            'metadata' => ['trial_credits' => 300, 'full_credits' => 2000],
                        ],
                    ]],
                ],
            ],
        ],
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'remaining_credit_amount' => 2000,
        'monthly_credit_amount' => 2000,
    ]);
});

test('invoice.payment_failed - it notifies users that payment has failed', function () {
    Notification::fake();

    Event::fake()->except([
        Hooks\WebhookReceived::class,
        Events\PaymentFailed::class,
    ]);

    Carbon::setTestNow(Carbon::create(2023, 9, 7));

    $plan = Plan::factory()->price()->create();
    $user = User::factory()->withSubscription($plan)->create([
        'stripe_id' => 'cus_foo-bar-baz',
        'monthly_credit_amount' => 500,
        'remaining_credit_amount' => 5000,
    ]);

    $this->postJson('stripe/webhook', [
        'type' => 'invoice.payment_failed',
        'data' => [
            'object' => [
                'object' => 'invoice',
                'customer' => $user->stripe_id,
                'customer_email' => $user->email,
                'next_payment_attempt' => now()->addDays(2)->timestamp,
            ],
        ],
    ]);

    Notification::assertSentTo($user, InvoicePaymentFailed::class);
});

test('customer.subscription.trial_will_end - notify and extends user trial', function () {
    Notification::fake();

    Event::fake()->except([
        Hooks\WebhookReceived::class,
        Events\TrialWillEnd::class,
    ]);

    Carbon::setTestNow(Carbon::create(2023, 9, 1));

    $plan = Plan::factory()->price()->create();
    $user = User::factory()->withTrial($plan)->create([
        'stripe_id' => 'cus_foo-bar-baz',
        'monthly_credit_amount' => 300,
        'remaining_credit_amount' => 300,
    ]);

    $subscription = $user->subscription();
    $subscription->items()->first();

    $this->mock(ExtendTrialAction::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->once()->andReturn(true);
    });

    $response = $this->postJson('stripe/webhook', [
        'type' => 'customer.subscription.trial_will_end',
        'data' => [
            'object' => [
                'object' => 'subscription',
                'customer' => $user->stripe_id,
                'customer_email' => $user->email,
                'status' => StripeSubscription::STATUS_TRIALING,
                'trial_start' => Carbon::create(2023, 9, 1)->timestamp,
                'trial_end' => Carbon::create(2023, 9, 8)->timestamp,
            ],
        ],
    ]);

    $response->assertOk();

    Notification::assertSentTo($user, TrialExtended::class);
});

test('customer.subscription.trial_will_end - notify user that trial will end', function () {
    Notification::fake();

    Event::fake()->except([
        Hooks\WebhookReceived::class,
        Events\TrialWillEnd::class,
    ]);

    Carbon::setTestNow(Carbon::create(2023, 9, 8));

    $plan = Plan::factory()->price()->create();
    $user = User::factory()->withTrial($plan)->create([
        'stripe_id' => 'cus_foo-bar-baz',
        'monthly_credit_amount' => 222,
        'remaining_credit_amount' => 300,
    ]);

    $subscription = $user->subscription();
    $subscription->items()->first();

    $response = $this->postJson('stripe/webhook', [
        'type' => 'customer.subscription.trial_will_end',
        'data' => [
            'object' => [
                'object' => 'subscription',
                'customer' => $user->stripe_id,
                'customer_email' => $user->email,
                'status' => StripeSubscription::STATUS_TRIALING,
                'trial_start' => Carbon::create(2023, 9, 1)->timestamp,
                'trial_end' => Carbon::create(2023, 9, 8)->timestamp,
            ],
        ],
    ]);

    $response->assertOk();

    Notification::assertSentTo($user, TrialWillEndReminder::class);
});

test('customer.subscription.created - it set initial account credits', function () {
    Notification::fake();

    Event::fake()->except([
        Hooks\WebhookHandled::class,
        Events\SubscriptionCreated::class,
    ]);

    $this->mock(GenerateWelcomeEmail::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->once()->andReturn(null);
    });

    $this->mock(TrackTrialStartedListener::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->once()->andReturn(null);
    });

    $product = Plan::factory()->product()->create();
    $plan = Plan::factory()->price()->create([
        'parent_id' => $product->id,
    ]);

    $user = User::factory()->withoutCredits()->create(['stripe_id' => 'cus_baz']);

    $response = $this->postJson('stripe/webhook', [
        'id' => 'foo',
        'type' => 'customer.subscription.created',
        'data' => [
            'object' => [
                'id' => 'sub_foo',
                'customer' => $user->stripe_id,
                'cancel_at_period_end' => false,
                'quantity' => 1,
                'items' => [
                    'data' => [[
                        'id' => 'bar',
                        'price' => [
                            'id' => $plan->plan_id,
                            'product' => 'prod_bar',
                            'recurring' => ['interval' => 'month'],
                            'metadata' => ['trial_credits' => 1000, 'full_credits' => 2000],
                        ],
                        'quantity' => 1,
                    ]],
                ],
                'status' => 'active',
            ],
        ],
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'remaining_credit_amount' => 2000,
        'monthly_credit_amount' => 2000,
    ]);
});

test('customer.subscription.created - it sends a subscription confirmation and welcome email', function () {
    Notification::fake();

    Event::fake()->except([
        Hooks\WebhookHandled::class,
        Events\SubscriptionCreated::class,
    ]);

    $this->mock(GenerateWelcomeEmail::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->once()->andReturn(null);
    });

    $this->mock(TrackTrialStartedListener::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->once()->andReturn(null);
    });

    $product = Plan::factory()->product()->create();
    $plan = Plan::factory()->price()->create([
        'parent_id' => $product->id,
    ]);

    $user = User::factory()->create(['stripe_id' => 'cus_baz']);

    $response = $this->postJson('stripe/webhook', [
        'id' => 'foo',
        'type' => 'customer.subscription.created',
        'data' => [
            'object' => [
                'id' => 'sub_foo',
                'customer' => $user->stripe_id,
                'cancel_at_period_end' => false,
                'quantity' => 1,
                'items' => [
                    'data' => [[
                        'id' => 'bar',
                        'price' => [
                            'id' => $plan->plan_id,
                            'product' => 'prod_bar',
                            'recurring' => ['interval' => 'month'],
                            'metadata' => ['trial_credits' => 1000, 'full_credits' => 2000],
                        ],
                        'quantity' => 1,
                    ]],
                ],
                'status' => 'active',
            ],
        ],
    ]);

    $response->assertOk();

    Notification::assertSentTo($user, SubscriptionConfirmation::class);
});

test('customer.subscription.created - it removes promo code after subscribing', function () {
    Notification::fake();

    Event::fake()->except([
        Hooks\WebhookHandled::class,
        Events\SubscriptionCreated::class,
    ]);

    $this->mock(GenerateWelcomeEmail::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->once()->andReturn(null);
    });

    $this->mock(TrackTrialStartedListener::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->once()->andReturn(null);
    });

    $product = Plan::factory()->product()->create();
    $plan = Plan::factory()->price()->create([
        'parent_id' => $product->id,
    ]);

    $user = User::factory()->create([
        'stripe_id' => 'cus_baz',
        'promo_code' => 'promo_foobar',
    ]);

    $response = $this->postJson('stripe/webhook', [
        'id' => 'foo',
        'type' => 'customer.subscription.created',
        'data' => [
            'object' => [
                'id' => 'sub_foo',
                'customer' => $user->stripe_id,
                'cancel_at_period_end' => false,
                'quantity' => 1,
                'items' => [
                    'data' => [[
                        'id' => 'bar',
                        'price' => [
                            'id' => $plan->plan_id,
                            'product' => 'prod_bar',
                            'recurring' => ['interval' => 'month'],
                            'metadata' => ['trial_credits' => 1000, 'full_credits' => 2000],
                        ],
                        'quantity' => 1,
                    ]],
                ],
                'status' => 'active',
            ],
        ],
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'promo_code' => null,
    ]);
});

test('customer.subscription.updated - it ensures new plan features after swapping', function () {
    Notification::fake();

    Event::fake()->except([
        Hooks\WebhookHandled::class,
        Events\SubscriptionUpdated::class,
    ]);

    $this->mock(TrackSubscriptionStartedListener::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->once()->andReturn(null);
    });

    Pixel::shouldReceive('track')->andReturn(null);
    TikTok::shouldReceive('track')->andReturn(null);

    // 7 days into plan
    Carbon::setTestNow($date = Carbon::create(2023, 9, 7));

    $product = Plan::factory()->product()->create();
    $plan = Plan::factory()->price()->create([
        'parent_id' => $product->id,
    ]);

    $user = User::factory()->withTrial($plan)->create([
        'stripe_id' => 'cus_foo-bar-baz',
    ]);

    Feature::resolveScopeUsing(fn ($driver) => $user);

    $subscription = $user->subscription();
    $item = $subscription->items()->first();

    Feature::define('watermark', true);
    Feature::define('max_scheduled_posts', 0);
    Feature::define('max_scheduled_weeks', 0);

    $this->postJson('stripe/webhook', [
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => $subscription->stripe_id,
                'object' => 'subscription',
                'customer' => $user->stripe_id,
                'trial_end' => $date->timestamp,
                'status' => StripeSubscription::STATUS_ACTIVE,
                'current_period_start' => Carbon::create(2023, 8, 31)->timestamp,
                'current_period_end' => Carbon::create(2023, 9, 30)->timestamp,
                'items' => [
                    'data' => [[
                        'id' => $item->stripe_id,
                        'price' => [
                            'id' => $subscription->stripe_price,
                            'product' => $item->stripe_product,
                            'metadata' => ['trial_credits' => 20000, 'full_credits' => 555555],
                        ],
                        'quantity' => 1,
                    ]],
                ],
            ],
            'previous_attributes' => [
                'items' => [
                    'data' => [[
                        'price' => [
                            'id' => $plan->plan_id,
                            'product' => 'prod_bar',
                            'metadata' => ['trial_credits' => 10000, 'full_credits' => 100000],
                        ],
                    ]],
                ],
            ],
        ],
    ]);

    $user = $user->fresh();

    Feature::define('watermark', false);
    Feature::define('max_scheduled_posts', $user->plan->details('features.max_scheduled_posts'));
    Feature::define('max_scheduled_weeks', $user->plan->details('features.max_scheduled_weeks'));
    Feature::flushCache();

    $this->assertFalse(Feature::value('watermark'));
    $this->assertSame($plan->details('features.max_scheduled_posts'), Feature::value('max_scheduled_posts'));
    $this->assertSame($plan->details('features.max_scheduled_weeks'), Feature::value('max_scheduled_weeks'));
});

test('customer.subscription.updated - it ensures full plan features and credits are enabled when trial ends', function () {
    Notification::fake();

    Event::fake()->except([
        Hooks\WebhookHandled::class,
        Events\SubscriptionUpdated::class,
    ]);

    Pixel::shouldReceive('track')->andReturn(null);
    TikTok::shouldReceive('track')->andReturn(null);

    // 7 days into plan
    Carbon::setTestNow($date = Carbon::create(2023, 9, 7));

    $product = Plan::factory()->product()->create();
    $plan = Plan::factory()->price()->create([
        'parent_id' => $product->id,
    ]);

    $user = User::factory()->withTrial($plan)->create([
        'stripe_id' => 'cus_foo-bar-baz',
    ]);

    Feature::resolveScopeUsing(fn ($driver) => $user);

    $subscription = $user->subscription();
    $item = $subscription->items()->first();

    Feature::define('watermark', true);
    Feature::define('max_scheduled_posts', 0);
    Feature::define('max_scheduled_weeks', 0);

    $this->postJson('stripe/webhook', [
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => $subscription->stripe_id,
                'object' => 'subscription',
                'customer' => $user->stripe_id,
                'trial_end' => $date->timestamp,
                'status' => StripeSubscription::STATUS_ACTIVE,
                'current_period_start' => Carbon::create(2023, 8, 31)->timestamp,
                'current_period_end' => Carbon::create(2023, 9, 30)->timestamp,
                'items' => [
                    'data' => [[
                        'id' => $item->stripe_id,
                        'price' => [
                            'id' => $subscription->stripe_price,
                            'product' => $item->stripe_product,
                            'metadata' => ['trial_credits' => 300, 'full_credits' => 1000],
                            'recurring' => ['interval' => 'month'],
                        ],
                        'quantity' => 1,
                    ]],
                ],
            ],
            'previous_attributes' => [
                'status' => StripeSubscription::STATUS_TRIALING,
            ],
        ],
    ]);

    $user = $user->fresh();
    expect($user)
        ->monthly_credit_amount->toBe(1000)
        ->remaining_credit_amount->toBe(1000);

    Feature::define('watermark', $user->onTrial());
    Feature::define('max_scheduled_posts', $user->plan->details('features.max_scheduled_posts'));
    Feature::define('max_scheduled_weeks', $user->plan->details('features.max_scheduled_weeks'));
    Feature::flushCache();

    $this->assertFalse(Feature::value('watermark'));
    $this->assertSame($plan->details('features.max_scheduled_posts'), Feature::value('max_scheduled_posts'));
    $this->assertSame($plan->details('features.max_scheduled_weeks'), Feature::value('max_scheduled_weeks'));
});

test('customer.subscription.updated - it sends an email when requested subscription cancellation', function () {
    Notification::fake();

    Event::fake()->except([
        Hooks\WebhookHandled::class,
        Events\SubscriptionUpdated::class,
    ]);

    $this->mock(TrackSubscriptionStartedListener::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->once()->andReturn(null);
    });

    $product = Plan::factory()->product()->create();
    $plan = Plan::factory()->price()->create([
        'parent_id' => $product->id,
    ]);

    $user = User::factory()->withSubscription($plan)->create([
        'stripe_id' => 'cus_foo-bar-baz',
    ]);

    $subscription = $user->subscription();
    $item = $subscription->items()->first();

    // 7 days into plan
    Carbon::setTestNow($date = Carbon::create(2023, 9, 7));

    $response = $this->postJson('stripe/webhook', [
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => $subscription->stripe_id,
                'object' => 'subscription',
                'customer' => $user->stripe_id,
                'current_period_end' => $date->endOfMonth()->timestamp,
                'current_period_start' => $date->startOfMonth()->timestamp,
                'cancel_at_period_end' => true,
                'canceled_at' => $date->timestamp,
                'cancel_at' => $date->endOfMonth()->timestamp,
                'cancellation_details' => [
                    'comment' => null,
                    'feedback' => null,
                    'reason' => 'cancellation_requested',
                ],
                'items' => [
                    'data' => [[
                        'id' => $item->stripe_id,
                        'price' => [
                            'id' => $subscription->stripe_price,
                            'product' => $item->stripe_product,
                        ],
                    ]],
                ],
            ],
            'previous_attributes' => [
                'cancel_at' => null,
                'cancel_at_period_end' => false,
                'canceled_at' => null,
                'cancellation_details' => [
                    'reason' => null,
                ],
            ],
        ],
    ]);

    $response->assertOk();

    Notification::assertSentTo($user, SubscriptionCancellation::class);
});

test('customer.subscription.deleted - it notifies the user of subscription termination', function () {
    Notification::fake();

    Event::fake()->except([
        Hooks\WebhookHandled::class,
        Events\SubscriptionDeleted::class,
    ]);

    Carbon::setTestNow($date = Carbon::create(2023, 9, 30));

    $plan = Plan::factory()->price()->create();
    $user = User::factory()->withSubscription($plan)->create([
        'stripe_id' => 'cus_foo-bar-baz',
    ]);

    $subscription = $user->subscription();
    $subscription->items()->first();

    $this->postJson('stripe/webhook', [
        'type' => 'customer.subscription.deleted',
        'data' => [
            'object' => [
                'id' => $subscription->stripe_id,
                'object' => 'subscription',
                'customer' => $user->stripe_id,
                'cancel_at' => null,
                'cancel_at_period_end' => false,
                'canceled_at' => $date->endOfMonth()->timestamp,
                'ended_at' => $date->endOfMonth()->timestamp,
                'cancellation_details' => [
                    'reason' => 'cancellation_requested',
                ],
            ],
        ],
    ]);

    Notification::assertSentTo($user, SubscriptionTermination::class);
});
