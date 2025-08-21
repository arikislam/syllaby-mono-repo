<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Syllaby\Publisher\Metrics\PublicationMetricKey;

/** @extends Factory<PublicationMetricKey> */
class PublicationMetricKeyFactory extends Factory
{
    protected $model = PublicationMetricKey::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => fn(array $attributes) => str()->slug($attributes['name']),
            'description' => fake()->sentence(5),
        ];
    }

    public function likes(): self
    {
        return $this->state([
            'name' => 'Likes Count',
            'slug' => 'likes-count',
        ]);
    }

    public function views(): self
    {
        return $this->state([
            'name' => 'Views Count',
            'slug' => 'views-count',
        ]);
    }

    public function comments(): self
    {
        return $this->state([
            'name' => 'Comments Count',
            'slug' => 'comments-count',
        ]);
    }

    public function dislikes(): self
    {
        return $this->state([
            'name' => 'Dislikes Count',
            'slug' => 'dislikes-count',
        ]);
    }
}
