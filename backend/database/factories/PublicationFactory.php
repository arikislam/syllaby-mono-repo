<?php

namespace Database\Factories;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Publication> */
class PublicationFactory extends Factory
{
    protected $model = Publication::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name,
            'video_id' => null
        ];
    }

    public function permanent(): self
    {
        return $this->state([
            'temporary' => false,
            'draft' => false,
        ]);
    }

    public function scheduled(Carbon $date): self
    {
        return $this->afterCreating(function (Publication $publication) use ($date) {
            $publication->event()->create([
                'user_id' => $publication->user_id,
                'starts_at' => $date,
                'ends_at' => $date,
            ]);
        });
    }
}
