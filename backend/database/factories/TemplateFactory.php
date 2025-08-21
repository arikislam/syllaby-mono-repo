<?php

namespace Database\Factories;

use App\Syllaby\Templates\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Template::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(3, false),
            'description' => null,
            'type' => fake()->randomElement(['video', 'article']),
            'metadata' => null,
            'source' => null,
            'is_active' => true,
        ];
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'video',
            'metadata' => [
                'width' => 1080,
                'height' => 1080,
                'format' => 'mp4',
                'aspect_ratio' => ['1:1', '16:9', '6:4', '9:16', '4:5'],
            ],
            'source' => [],
        ]);
    }
}
