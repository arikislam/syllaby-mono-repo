<?php

namespace Database\Factories;

use App\Syllaby\Speeches\Voice;
use App\Syllaby\Speeches\Enums\SpeechProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Voice::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => fake()->name(),
            'gender' => fake()->randomElement(['male', 'female']),
            'language' => null,
            'accent' => null,
            'preview_url' => fake()->url(),
            'provider' => fake()->randomElement([SpeechProvider::ELEVENLABS]),
            'provider_id' => fake()->uuid(),
            'type' => Voice::STANDARD,
            'words_per_minute' => fake()->numberBetween(90, 136),
            'is_active' => true,
            'metadata' => [],
        ];
    }
}
