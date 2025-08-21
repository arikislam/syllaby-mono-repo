<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Plan::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'plan_id' => 'prod_'.Str::random(6),
            'price' => fake()->numberBetween(20, 100),
            'name' => fake()->name(),
            'slug' => fake()->unique()->slug(),
            'active' => true,
        ];
    }

    public function product(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => 'prod_'.Str::random(6),
            'type' => 'product',
            'parent_id' => null,
            'price' => 0,
            'currency' => null,
        ]);
    }

    public function price(string $type = 'month'): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => 'price_'.Str::random(6),
            'type' => $type,
            'parent_id' => null,
            'price' => fake()->numberBetween(400, 10000),
            'currency' => 'USD',
            'meta' => ['trial_credits' => 300, 'full_credits' => 1000],
        ]);
    }
}
