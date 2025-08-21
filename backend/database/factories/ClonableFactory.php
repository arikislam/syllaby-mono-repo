<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Clonables\Clonable;
use App\Syllaby\Subscriptions\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClonableFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Clonable::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'purchase_id' => Purchase::factory(),
            'model_id' => null,
            'model_type' => null,
            'status' => null,
            'metadata' => null,
        ];
    }
}
