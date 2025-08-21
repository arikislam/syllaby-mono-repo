<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Subscriptions\Plan;
use App\Syllaby\Subscriptions\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Purchase::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory()->price(),

            'payment_intent' => null,
            'status' => null,
        ];
    }
}
