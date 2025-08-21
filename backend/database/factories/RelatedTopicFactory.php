<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use App\Syllaby\Ideas\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

class RelatedTopicFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Topic::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence,
            'type' => $this->faker->randomElement(['video']),
            'ideas' => json_encode($this->faker->paragraphs(3)),
            'provider' => 'gpt',
            'metadata' => null,
        ];
    }
}
