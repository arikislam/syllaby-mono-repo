<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Enums\VideoStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Video::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'scheduler_id' => null,
            'idea_id' => null,
            'title' => 'Untitled video',
            'provider' => 'creatomate',
            'provider_id' => 'ba9a76344fb8445694dc6e54666f6c23',
            'status' => VideoStatus::DRAFT,
            'synced_at' => null,
            'url' => fake()->url(),
            'retries' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (array $attributes) => [
            'url' => null,
            'synced_at' => null,
            'status' => VideoStatus::DRAFT,
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'synced_at' => now(),
            'status' => VideoStatus::COMPLETED,
        ]);
    }

    public function rendering(): self
    {
        return $this->state(fn (array $attributes) => [
            'url' => null,
            'synced_at' => null,
            'status' => VideoStatus::RENDERING,
        ]);
    }

    public function syncing(): self
    {
        return $this->state(fn (array $attributes) => [
            'url' => null,
            'synced_at' => null,
            'status' => VideoStatus::SYNCING,
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'url' => null,
            'synced_at' => null,
            'status' => VideoStatus::FAILED,
        ]);
    }

    public function modified(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => VideoStatus::MODIFIED,
        ]);
    }

    public function modifying(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => VideoStatus::MODIFYING,
        ]);
    }

    public function faceless(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => Video::FACELESS,
        ]);
    }

    public function custom(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => Video::CUSTOM,
        ]);
    }
}
