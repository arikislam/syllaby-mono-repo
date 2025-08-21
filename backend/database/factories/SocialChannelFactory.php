<?php

namespace Database\Factories;

use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Channels\SocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SocialChannel> */
class SocialChannelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = SocialChannel::class;

    public function definition(): array
    {
        return [
            'social_account_id' => SocialAccount::factory(),
            'name' => fake()->name,
            'avatar' => fake()->imageUrl(),
            'provider_id' => fake()->uuid(),
            'type' => SocialChannel::INDIVIDUAL,
        ];
    }

    public function organization(): self
    {
        return $this->state(fn() => [
            'type' => SocialChannel::ORGANIZATION,
        ]);
    }

    public function individual(): self
    {
        return $this->state(fn() => [
            'type' => SocialChannel::INDIVIDUAL,
        ]);
    }

    public function page(): self
    {
        return $this->state(fn() => [
            'type' => SocialChannel::PAGE
        ]);
    }

    public function professional(): self
    {
        return $this->state(fn() => [
            'type' => SocialChannel::PROFESSIONAL_ACCOUNT
        ]);
    }
}
