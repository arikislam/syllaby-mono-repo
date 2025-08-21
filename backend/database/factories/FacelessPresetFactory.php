<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Media;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Videos\Enums\Overlay;
use App\Syllaby\Presets\FacelessPreset;
use App\Syllaby\Videos\Enums\Dimension;
use App\Syllaby\Videos\Enums\StoryGenre;
use App\Syllaby\Videos\Enums\Transition;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Syllaby\Videos\Vendors\Faceless\Builder\FontPresets;

class FacelessPresetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = FacelessPreset::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'voice_id' => Voice::factory(),
            'music_id' => Media::factory(),
            'background_id' => null,
            'genre_id' => null,
            'language' => 'english',
            'font_family' => $this->faker->randomElement(FontPresets::values()),
            'font_color' => $this->faker->hexColor(),
            'position' => 'center',
            'duration' => $this->faker->numberBetween(30, 300),
            'orientation' => $this->faker->randomElement(Dimension::values()),
            'transition' => $this->faker->randomElement(Transition::cases()),
            'volume' => $this->faker->randomElement(['low', 'medium', 'high']),
            'sfx' => $this->faker->randomElement(['whoosh', 'none']),
            'overlay' => $this->faker->randomElement(Overlay::values()),
        ];
    }
}
