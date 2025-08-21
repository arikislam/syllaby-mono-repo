<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Folders\Folder;
use App\Syllaby\Folders\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resource>
 */
class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'model_id' => $this->faker->randomNumber(),
            'model_type' => $this->faker->randomElement([Folder::class]),
            'parent_id' => null,
        ];
    }

    public function asChildOf(Resource $parent): self
    {
        return $this->state([
            'parent_id' => $parent->id,
        ]);
    }
}
