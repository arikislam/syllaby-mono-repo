<?php

namespace Database\Factories;

use App\Syllaby\Metadata\Metadata;
use Illuminate\Database\Eloquent\Factories\Factory;

class MetadataFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Metadata::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'type' => fake()->word(),
            'provider' => fake()->word(),
            'key' => fake()->word(),
            'values' => [],
        ];
    }

    /**
     * Youtube Categories
     */
    public function categories(): self
    {
        return $this->state(fn () => [
            'type' => 'social-upload',
            'provider' => 'youtube',
            'key' => 'categories',
            'values' => [
                [
                    'id' => fake()->numberBetween(1, 100),
                    'title' => fake()->word(),
                ],
            ],
        ]);
    }
}
