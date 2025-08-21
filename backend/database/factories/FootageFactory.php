<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Footage;
use Illuminate\Database\Eloquent\Factories\Factory;

class FootageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Footage::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'video_id' => Video::factory(),
            'preference' => [],
            'hash' => null,
        ];
    }
}
