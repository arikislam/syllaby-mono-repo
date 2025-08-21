<?php

namespace Database\Factories;

use App\Syllaby\Publisher\Metrics\PublicationAggregate;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Database\Eloquent\Factories\Factory;

class PublicationAggregateFactory extends Factory
{
    protected $model = PublicationAggregate::class;

    public function definition(): array
    {
        return [
            'publication_id' => Publication::factory(),
            'social_channel_id' => SocialChannel::factory(),
            'key' => $this->faker->randomElement(['views', 'likes', 'comments']),
            'value' => $this->faker->numberBetween(0, 1000),
            'type' => $this->faker->randomElement(['sum', 'average']),
            'last_updated_at' => $this->faker->dateTime(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
