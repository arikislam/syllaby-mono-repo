<?php

namespace Database\Factories;

use Stripe\Subscription;
use App\Syllaby\Users\User;
use Illuminate\Support\Str;
use App\Syllaby\Folders\Resource;
use App\Syllaby\Subscriptions\Plan;
use App\Syllaby\Users\Enums\UserType;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '12345678', // password
            'remember_token' => Str::random(10),
            'user_type' => UserType::CUSTOMER,
            'settings' => config('syllaby.users.settings'),
            'notifications' => config('syllaby.users.notifications'),
            'promo_code' => null,
            'pm_exemption_code' => null,
            'plan_id' => null,
            'monthly_credit_amount' => 500,
            'remaining_credit_amount' => 500,
            'active' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withoutCredits(): static
    {
        return $this->state(fn (array $attributes) => [
            'monthly_credit_amount' => 0,
            'remaining_credit_amount' => 0,
        ]);
    }

    /**
     * Indicate that the user should have a subscription plan.
     */
    public function withSubscription(Plan $plan): static
    {
        return $this->afterCreating(function (User $user) use ($plan) {
            $user->update(['plan_id' => $plan->id]);

            $subscription = $user->subscriptions()->create([
                'type' => 'default',
                'stripe_id' => 'sub_'.Str::random(10),
                'stripe_status' => Subscription::STATUS_ACTIVE,
                'stripe_price' => $plan->plan_id,
                'quantity' => 1,
                'trial_ends_at' => null,
                'ends_at' => null,
            ]);

            $subscription->items()->create([
                'stripe_id' => 'si_'.Str::random(10),
                'stripe_product' => 'prod_'.Str::random(10),
                'stripe_price' => $plan->plan_id,
                'quantity' => 1,
            ]);
        });
    }

    /**
     * Indicate that the user should have a subscription plan.
     *
     * @return $this
     */
    public function withTrial(Plan $plan, int $days = 7): static
    {
        return $this->afterCreating(function (User $user) use ($plan, $days) {
            $user->update(['plan_id' => $plan->id]);

            $subscription = $user->subscriptions()->create([
                'type' => 'default',
                'stripe_id' => 'sub_'.Str::random(10),
                'stripe_status' => Subscription::STATUS_TRIALING,
                'stripe_price' => $plan->plan_id,
                'quantity' => 1,
                'trial_ends_at' => now()->addDays($days),
                'ends_at' => null,
            ]);

            $subscription->items()->create([
                'stripe_id' => 'si_'.Str::random(10),
                'stripe_product' => 'prod_'.Str::random(10),
                'stripe_price' => $plan->plan_id,
                'quantity' => 1,
            ]);
        });
    }

    public function google(): self
    {
        return $this->state(fn (array $attributes) => [
            'provider' => SocialAccountEnum::Google->value,
            'provider_id' => Str::random(10),
        ]);
    }

    public function withDefaultFolder(): self
    {
        return $this->afterCreating(function (User $user) {
            $folder = $user->folders()->create(['name' => 'Default']);
            Resource::create(['user_id' => $user->id, 'model_id' => $folder->id, 'model_type' => 'folder', 'parent_id' => null]);
        });
    }

    public function admin(): self
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => UserType::ADMIN,
        ]);
    }
}
