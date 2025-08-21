<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Bookmarks\Bookmark;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookmarkFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Bookmark::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'model_id' => null,
            'model_type' => null,
        ];
    }
}
