<?php

namespace Database\Factories;

use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Syllaby\Publisher\Metrics\PublicationMetricKey;
use App\Syllaby\Publisher\Metrics\PublicationMetricValue;

/** @extends Factory<PublicationMetricValue> */
class PublicationMetricValueFactory extends Factory
{
    protected $model = PublicationMetricValue::class;

    public function definition(): array
    {
        return [
            'publication_id' => Publication::factory(),
            'social_channel_id' => SocialChannel::factory(),
            'publication_metric_key_id' => PublicationMetricKey::factory(),
            'value' => fake()->numberBetween(0, 1000)
        ];
    }
}
