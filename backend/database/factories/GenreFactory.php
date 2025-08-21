<?php

namespace Database\Factories;

use App\Syllaby\Characters\Genre;
use Illuminate\Database\Eloquent\Factories\Factory;

class GenreFactory extends Factory
{
    protected $model = Genre::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'slug' => $this->faker->slug,
            'meta' => [
                'prompt' => $this->faker->sentence,
                'input' => [
                    'width' => 512,
                    'height' => 512,
                ]
            ],
            'details' => [],
            'consistent_character' => false,
            'active' => false,
            'prompt' => $this->faker->sentence,
        ];
    }

    public function active(): static
    {
        return $this->state(['active' => true]);
    }

    public function consistent(): static
    {
        return $this->state(['consistent_character' => true]);
    }
}