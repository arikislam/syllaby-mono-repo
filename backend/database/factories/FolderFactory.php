<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Folders\Folder;
use App\Syllaby\Folders\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Folder>
 */
class FolderFactory extends Factory
{
    protected $model = Folder::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->word(),
            'color' => $this->faker->hexColor(),
        ];
    }

    public function configure(): self
    {
        return $this->afterCreating(fn (Folder $folder) => Resource::factory()->create([
            'user_id' => $folder->user_id,
            'model_id' => $folder->id,
            'model_type' => 'folder',
            'parent_id' => null,
        ]));
    }
}
