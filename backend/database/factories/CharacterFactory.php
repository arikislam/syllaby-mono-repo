<?php

namespace Database\Factories;

use App\Syllaby\Characters\Character;
use App\Syllaby\Characters\Enums\CharacterStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterFactory extends Factory
{
    protected $model = Character::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid,
            'user_id' => null,
            'name' => $this->faker->name,
            'slug' => $this->faker->slug,
            'description' => $this->faker->paragraph,
            'gender' => $this->faker->randomElement(['male', 'female']),
            'active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(['active' => true]);
    }

    public function ready(): static
    {
        return $this->state([
            'status' => CharacterStatus::READY,
        ]);
    }

    public function draft(): static
    {
        return $this->state([
            'status' => CharacterStatus::DRAFT,
        ]);
    }

    public function previewReady(): static
    {
        return $this->state([
            'status' => CharacterStatus::PREVIEW_READY,
        ]);
    }

    public function modelTraining(): static
    {
        return $this->state([
            'status' => CharacterStatus::MODEL_TRAINING,
        ]);
    }

    public function poseGenerating(): static
    {
        return $this->state([
            'status' => CharacterStatus::POSE_GENERATING,
        ]);
    }
}
