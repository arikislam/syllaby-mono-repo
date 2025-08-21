<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use Illuminate\Support\Str;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Videos\Enums\Dimension;
use App\Syllaby\Assets\Enums\AssetStatus;
use App\Syllaby\Assets\Enums\AssetProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Asset> */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => $this->faker->randomElement(AssetProvider::values()),
            'provider_id' => $this->faker->uuid,
            'type' => $this->faker->randomElement(AssetType::values()),
            'name' => $this->faker->name,
            'slug' => fn (array $attributes) => Str::slug($attributes['name']),
            'genre_id' => null,
            'description' => null,
            'is_private' => false,
            'status' => $this->faker->randomElement(AssetStatus::values()),
            'model' => 'video-01',
            'retries' => 0,
        ];
    }

    public function withMedia(string $mime = 'image/jpeg'): self
    {
        return $this->afterCreating(fn (Asset $asset) => $asset->media()->create([
            'name' => 'test',
            'file_name' => 'test.jpg',
            'collection_name' => 'default',
            'model_id' => $asset->id,
            'model_type' => $asset->getMorphClass(),
            'disk' => 'spaces',
            'size' => 1000,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'mime_type' => $mime,
        ]));
    }

    public function portrait(): self
    {
        return $this->state(fn (array $attributes) => [
            'orientation' => Dimension::PORTRAIT->value,
        ]);
    }

    public function landscape(): self
    {
        return $this->state(fn (array $attributes) => [
            'orientation' => Dimension::LANDSCAPE->value,
        ]);
    }

    public function global(): self
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    public function aiVideo(): self
    {
        return $this->state(fn (array $attributes) => [
            'provider' => AssetProvider::MINIMAX,
            'type' => AssetType::AI_VIDEO,
        ]);
    }

    public function aiImage(): self
    {
        return $this->state(fn (array $attributes) => [
            'provider' => AssetProvider::REPLICATE,
            'type' => AssetType::AI_IMAGE,
        ]);
    }

    public function processing(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetStatus::PROCESSING,
        ]);
    }

    public function success(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetStatus::SUCCESS,
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetStatus::FAILED,
        ]);
    }

    public function watermark(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => AssetType::WATERMARK,
        ]);
    }

    public function audio(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => AssetType::AUDIOS,
        ]);
    }
}
