<?php

namespace Database\Factories;

use App\Syllaby\Editor\EditorAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

class EditorAssetFactory extends Factory
{
    protected $model = EditorAsset::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement([EditorAsset::TEXT_PRESET, EditorAsset::FONT]),
            'preview' => ['url' => fake()->imageUrl()],
            'key' => fake()->unique()->word(),
            'value' => [
                'foo' => 'bar',
                'baz' => 'qux'
            ],
        ];
    }

    public function preset(): EditorAssetFactory
    {
        return $this->state(fn() => ['type' => EditorAsset::TEXT_PRESET]);
    }

    public function font(): EditorAssetFactory
    {
        return $this->state(fn() => ['type' => EditorAsset::FONT]);
    }
}
