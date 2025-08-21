<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\Occurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Syllaby\SchedulersOccurrence>
 */
class OccurrenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Occurrence::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'scheduler_id' => Scheduler::factory(),
            'topic' => fake()->sentence(),
            'script' => fake()->sentence(),
            'occurs_at' => fake()->dateTimeBetween('now', '+1 month'),
            'status' => fake()->randomElement(['pending', 'completed', 'failed']),
        ];
    }
}
