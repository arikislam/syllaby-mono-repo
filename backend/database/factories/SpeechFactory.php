<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Speeches\Speech;
use App\Syllaby\RealClones\RealClone;
use App\Syllaby\Speeches\Enums\SpeechStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpeechFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Speech::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'real_clone_id' => RealClone::factory(),
            'voice_id' => Voice::factory(),
            'provider_id' => fake()->word(),
            'provider' => 'elevenlabs',
            'url' => fake()->url(),
            'status' => SpeechStatus::COMPLETED->value,
            'synced_at' => null,
            'is_custom' => false,
        ];
    }
}
