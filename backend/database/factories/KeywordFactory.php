<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Syllaby\Ideas\Keyword;
use App\Syllaby\Ideas\Enums\Networks;
use Illuminate\Database\Eloquent\Factories\Factory;

class KeywordFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Keyword::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $name = fake()->unique()->words(3, true),
            'slug' => Str::slug($name),
            'network' => fake()->randomElement(Networks::toArray()),
            'source' => 'keywordtool',
        ];
    }
}
