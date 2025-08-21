<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Trackers\Tracker;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrackerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Tracker::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'trackable_id' => fake()->randomNumber(),
            'trackable_type' => fake()->word(),
            'name' => fake()->name(),
            'count' => 0,
            'limit' => 3,
        ];
    }
}
