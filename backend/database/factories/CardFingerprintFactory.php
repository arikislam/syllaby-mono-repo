<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CardFingerprintFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'fingerprint' => fake()->sha256(),
        ];
    }
}
