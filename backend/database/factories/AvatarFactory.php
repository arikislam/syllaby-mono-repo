<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\RealClones\Avatar;
use App\Syllaby\RealClones\Enums\RealCloneProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvatarFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Avatar::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'gender' => fake()->randomElement(['male', 'female']),
            'preview_url' => fake()->url(),
            'provider' => fake()->randomElement(RealCloneProvider::toArray()),
            'provider_id' => fake()->uuid(),
            'type' => Avatar::STANDARD,
            'is_active' => true,
            'metadata' => [],
        ];
    }
}
