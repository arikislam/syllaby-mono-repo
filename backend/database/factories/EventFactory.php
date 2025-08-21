<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Planner\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'scheduler_id' => null,
            'color' => fake()->hexColor(),
            'model_id' => null,
            'model_type' => fake()->randomElement(['video', 'publication']),
            'starts_at' => fake()->dateTime(),
            'ends_at' => fake()->dateTime(),
            'completed_at' => null,
            'created_at' => fake()->dateTime(),
            'updated_at' => fake()->dateTime(),
        ];
    }
}
