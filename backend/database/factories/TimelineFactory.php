<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Metadata\Timeline;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimelineFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Timeline::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'model_id' => null,
            'model_type' => null,
            'content' => [],
            'hash' => null,
            'provider' => 'creatomate',
        ];
    }
}
