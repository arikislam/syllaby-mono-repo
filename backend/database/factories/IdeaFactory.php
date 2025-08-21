<?php

namespace Database\Factories;

use App\Syllaby\Ideas\Idea;
use Illuminate\Support\Str;
use App\Syllaby\Ideas\Keyword;
use Illuminate\Database\Eloquent\Factories\Factory;

class IdeaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Idea::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'keyword_id' => Keyword::factory(),
            'title' => $title = fake()->unique()->words(3, true),
            'slug' => Str::slug($title),
            'type' => 'suggestions',
            'currency' => fake()->currencyCode(),
            'locale' => fake()->locale(),
            'volume' => fake()->randomDigit(),
            'cpc' => fake()->randomDigit(),
            'competition' => fake()->randomDigit(),
            'competition_label' => 'low',
            'total_results' => fake()->randomDigit(),
            'trend' => fake()->randomDigit(),
            'trends' => fake()->word(),
            'valid_until' => fake()->dateTime(),
            'deleted_at' => null,
        ];
    }

    public function public(): self
    {
        return $this->state(['public' => true]);
    }

    public function private(): self
    {
        return $this->state(['public' => false]);
    }
}
