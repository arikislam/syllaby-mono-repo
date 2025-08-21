<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Subscriptions\JVZooPlan;
use App\Syllaby\Subscriptions\JVZooSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class JVZooSubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = JVZooSubscription::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'jvzoo_plan_id' => JVZooPlan::factory(),
            'receipt' => strtoupper(fake()->unique()->bothify('?#?#?#?#?#?#?#?#')),
            'status' => JVZooSubscription::STATUS_ACTIVE,
            'started_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'ends_at' => null,
            'trial_ends_at' => null,
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JVZooSubscription::STATUS_ACTIVE,
            'ends_at' => null,
        ]);
    }

    /**
     * Indicate that the subscription is on trial.
     */
    public function onTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JVZooSubscription::STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(7),
        ]);
    }

    /**
     * Indicate that the subscription is canceled.
     */
    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JVZooSubscription::STATUS_CANCELED,
            'ends_at' => now()->addDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Indicate that the subscription is expired (past due).
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JVZooSubscription::STATUS_EXPIRED,
            'ends_at' => now()->subDays(fake()->numberBetween(1, 10)),
        ]);
    }

    /**
     * Indicate that the subscription has payment failure metadata.
     */
    public function withPaymentFailure(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JVZooSubscription::STATUS_EXPIRED,
        ]);
    }
}
