<?php

namespace Database\Factories;

use App\Syllaby\Subscriptions\Plan;
use App\Syllaby\Subscriptions\JVZooPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class JVZooPlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = JVZooPlan::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'is_active' => true,
            'jvzoo_id' => fake()->unique()->numberBetween(100, 999),
            'name' => fake()->name(),
            'price' => fake()->numberBetween(10, 999),
            'currency' => 'USD',
            'interval' => fake()->randomElement(['month', 'year']),
            'metadata' => [
                'created_from_webhook' => false,
                'full_credits' => 500,
                'trial_credits' => 50,
            ],
        ];
    }

    /**
     * Indicate that the plan is monthly.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'interval' => 'month',
        ]);
    }

    /**
     * Indicate that the plan is yearly.
     */
    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'interval' => 'year',
        ]);
    }
}
