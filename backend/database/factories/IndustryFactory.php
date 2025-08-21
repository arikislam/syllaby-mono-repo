<?php

namespace Database\Factories;

use App\Syllaby\Surveys\Industry;
use Illuminate\Database\Eloquent\Factories\Factory;

class IndustryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Industry::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'slug' => fake()->unique()->slug(),
        ];
    }
}
