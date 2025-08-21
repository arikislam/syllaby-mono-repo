<?php

namespace Database\Factories;

use App\Syllaby\Ideas\Idea;
use App\Syllaby\Ideas\Keyword;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Idea>
 */
class KeywordSearchResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'keyword_id' => Keyword::factory(),

            'question' => $this->faker->word,
            'search_volume' => $this->faker->randomDigit(),
            'cpc' => $this->faker->randomDigit(),
            'competition' => $this->faker->randomDigit(),
            'number_of_results' => $this->faker->randomDigit(),
            'trends' => $this->faker->word,

            'valid_until' => $this->faker->dateTime(),
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
        ];
    }
}
