<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Surveys\UserFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFeedbackFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = UserFeedback::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reason' => fake()->sentence(),
            'details' => fake()->sentence(),
        ];
    }
}
