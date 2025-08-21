<?php

namespace Database\Factories;

use App\Syllaby\Generators\Generator;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeneratorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Generator::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'model_id' => null,
            'model_type' => null,
            'topic' => fake()->sentence(),
            'length' => null,
            'tone' => 'formal',
            'style' => 'explainer',
            'language' => 'english',
            'context' => null,
            'output' => null,
        ];
    }
}
