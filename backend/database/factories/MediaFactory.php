<?php

namespace Database\Factories;

use App\Syllaby\Assets\Media;
use App\Syllaby\Videos\Enums\Dimension;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @mixin Media */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'model_type' => 'App\Models\YourModel',
            'model_id' => 1,
            'uuid' => fake()->uuid(),
            'collection_name' => 'default',
            'name' => fake()->word(),
            'file_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'spaces',
            'conversions_disk' => 'spaces',
            'size' => fake()->randomNumber(),
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'order_column' => 1,
        ];
    }

    public function youtube(): self
    {
        return $this->state(fn () => [
            'mime_type' => 'video/mp4',
            'file_name' => "{$this->faker->word()}.mp4",
            'collection_name' => 'publications',
            'size' => 1024 * 1024 * 10,
        ]);
    }

    public function fbReel(): self
    {
        return $this->state(fn () => [
            'mime_type' => 'video/mp4',
            'file_name' => "{$this->faker->word()}.mp4",
            'collection_name' => 'publications',
            'size' => 1024 * 1024 * 10,
            'custom_properties' => [
                'width' => 1920,
                'height' => 1080,
                'codec' => 'h264',
                'aspect_ratio' => '16:9',
                'frame_rate' => 30,
                'resolution' => '1920x1080',
                'duration' => 60,
                'orientation' => Dimension::PORTRAIT->value,
            ],
        ]);
    }

    public function fbPost(): self
    {
        return $this->state(fn () => [
            'mime_type' => 'video/mp4',
            'file_name' => "{$this->faker->word()}.mp4",
            'collection_name' => 'publications',
            'size' => 1024 * 1024 * 10,
            'custom_properties' => [
                'width' => 1920,
                'height' => 1080,
                'aspect_ratio' => '16:9',
                'frame_rate' => 30,
                'resolution' => '1920x1080',
                'duration' => 120,
            ],
        ]);
    }

    public function fbStory(): self
    {
        return $this->state(fn () => [
            'mime_type' => 'video/mp4',
            'file_name' => "{$this->faker->word()}.mp4",
            'collection_name' => 'publications',
            'size' => 1024 * 1024 * 10,
            'custom_properties' => [
                'width' => 1080,
                'height' => 1920,
                'aspect_ratio' => '9:16',
                'frame_rate' => 30,
                'resolution' => '1080x1920',
                'duration' => 15,
                'orientation' => Dimension::PORTRAIT->value,
            ],
        ]);
    }

    public function tiktokShort(): self
    {
        return $this->state(fn () => [
            'mime_type' => 'video/mp4',
            'file_name' => "{$this->faker->word()}.mp4",
            'collection_name' => 'publications',
            'size' => 1024 * 1024 * 10,
        ]);
    }

    public function linkedInVideo(): self
    {
        return $this->state(fn () => [
            'mime_type' => 'video/mp4',
            'file_name' => "{$this->faker->word()}.mp4",
            'collection_name' => 'publications',
            'size' => 1024 * 1024 * 10,
        ]);
    }

    public function instaReel(): self
    {
        return $this->state(fn () => [
            'mime_type' => 'video/mp4',
            'file_name' => "{$this->faker->word()}.mp4",
            'collection_name' => 'publications',
            'size' => 1024 * 1024 * 10,
            'custom_properties' => [
                'width' => 1080,
                'height' => 1920,
                'aspect_ratio' => '9:16',
                'frame_rate' => 30,
                'resolution' => '1080x1920',
                'duration' => 15,
                'orientation' => Dimension::PORTRAIT->value,
            ],
        ]);
    }

    public function instaStory(): self
    {
        return $this->state(fn () => [
            'mime_type' => 'video/mp4',
            'file_name' => "{$this->faker->word()}.mp4",
            'collection_name' => 'publications',
            'size' => 1024 * 1024 * 10,
            'custom_properties' => [
                'width' => 1080,
                'height' => 1920,
                'aspect_ratio' => '9:16',
                'frame_rate' => 30,
                'resolution' => '1080x1920',
                'duration' => 15,
                'orientation' => Dimension::PORTRAIT->value,
            ],
        ]);
    }

    public function threadsPost(): self
    {
        return $this->state(fn () => [
            'mime_type' => 'video/mp4',
            'file_name' => "{$this->faker->word()}.mp4",
            'collection_name' => 'publications',
            'size' => 1024 * 1024 * 10,
            'custom_properties' => [
                'width' => 1080,
                'height' => 1920,
                'aspect_ratio' => '9:16',
                'frame_rate' => 30,
                'resolution' => '1080x1920',
                'duration' => 15,
                'orientation' => Dimension::PORTRAIT->value,
            ],
        ]);
    }
}
