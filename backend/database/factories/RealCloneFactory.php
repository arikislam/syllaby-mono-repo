<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Videos\Footage;
use App\Syllaby\RealClones\Avatar;
use App\Syllaby\RealClones\RealClone;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class RealCloneFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = RealClone::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'footage_id' => Footage::factory(),
            'voice_id' => Voice::factory(),
            'avatar_id' => Avatar::factory(),
            'background' => 'transparent',
            'provider_id' => fake()->uuid(),
            'provider' => fake()->randomElement(['heygen', 'd-id']),
            'url' => fake()->url(),
            'script' => fake()->sentence(),
            'status' => RealCloneStatus::DRAFT,
            'hash' => null,
            'retries' => 0,
            'synced_at' => null,
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (array $attributes) => [
            'url' => null,
            'synced_at' => null,
            'status' => RealCloneStatus::DRAFT,
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'synced_at' => now(),
            'status' => RealCloneStatus::COMPLETED,
        ]);
    }

    public function generating(): self
    {
        return $this->state(fn (array $attributes) => [
            'url' => null,
            'synced_at' => null,
            'status' => RealCloneStatus::GENERATING,
        ]);
    }

    public function syncing(): self
    {
        return $this->state(fn (array $attributes) => [
            'url' => null,
            'synced_at' => null,
            'status' => RealCloneStatus::SYNCING,
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'url' => null,
            'synced_at' => null,
            'status' => RealCloneStatus::FAILED,
        ]);
    }
}
