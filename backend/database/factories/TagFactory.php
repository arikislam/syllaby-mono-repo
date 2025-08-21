<?php

namespace Database\Factories;

use App\Syllaby\Tags\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => fake()->word(),
            'slug' => fake()->unique()->slug(),
            'color' => fake()->hexColor(),
        ];
    }
}
