<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Video;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Videos\Enums\Sfx;
use App\Syllaby\Videos\DTOs\Options;
use App\Syllaby\Generators\Generator;
use App\Syllaby\Videos\Enums\FacelessType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Faceless> */
class FacelessFactory extends Factory
{
    protected $model = Faceless::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'video_id' => Video::factory(),
            'background_id' => Asset::factory(),
            'estimated_duration' => 60,
            'music_id' => null,
            'genre_id' => null,
            'character_id' => null,
            'voice_id' => Voice::factory()->create()->id,
            'script' => fake()->text,
            'is_transcribed' => false,
            'type' => FacelessType::AI_VISUALS,
            'options' => new Options('lively', 'center', '9:16', Sfx::WHOOSH->value, 'default'),
            'batch' => null,
            'hash' => ['speech' => '', 'options' => ''],
        ];
    }

    public function withSource(): self
    {
        return $this->state(function ($attributes) {
            return [
                'hash' => [
                    'options' => md5(serialize($attributes['options'])),
                    'speech' => md5(serialize([$attributes['script'], $attributes['voice_id']])),
                ],
            ];
        });
    }

    public function ai(): self
    {
        return $this->state(fn (array $attributes) => [
            'background_id' => null,
            'type' => FacelessType::AI_VISUALS,
        ]);
    }

    public function broll(): self
    {
        return $this->state(fn (array $attributes) => [
            'genre_id' => null,
            'background_id' => null,
            'type' => FacelessType::B_ROLL,
        ]);
    }

    public function singleClip(): self
    {
        return $this->state(fn (array $attributes) => [
            'genre_id' => null,
            'type' => FacelessType::SINGLE_CLIP,
        ]);
    }

    public function urlBased(): self
    {
        return $this->state(fn (array $attributes) => [
            'genre_id' => null,
            'type' => FacelessType::URL_BASED,
        ]);
    }

    public function configure(): self
    {
        return $this->afterCreating(function (Faceless $faceless) {
            Generator::factory()->for($faceless, 'model')->create();
        });
    }
}
