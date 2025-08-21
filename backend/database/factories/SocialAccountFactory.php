<?php

namespace Database\Factories;

use App\Syllaby\Users\User;
use Illuminate\Support\Str;
use App\Syllaby\Publisher\Channels\SocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = SocialAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => fake()->randomElement([0, 1]),
            'provider_id' => fake()->uuid(),
            'access_token' => Str::random(50),
            'expires_in' => 200,
            'refresh_token' => 'refresh-token',
            'refresh_expires_in' => null,
        ];
    }

    public function youtube(): SocialAccountFactory
    {
        return $this->state(fn () => [
            'provider' => SocialAccountEnum::Youtube->value,
        ]);
    }

    public function tiktok(): SocialAccountFactory
    {
        return $this->state(fn () => [
            'provider' => SocialAccountEnum::TikTok->value,
        ]);
    }

    public function linkedin(): SocialAccountFactory
    {
        return $this->state(fn () => [
            'provider' => SocialAccountEnum::LinkedIn->value,
        ]);
    }

    public function facebook(): SocialAccountFactory
    {
        return $this->state(fn () => [
            'provider' => SocialAccountEnum::Facebook->value,
        ]);
    }

    public function instagram(): SocialAccountFactory
    {
        return $this->state(fn () => [
            'provider' => SocialAccountEnum::Instagram->value,
        ]);
    }

    public function threads(): SocialAccountFactory
    {
        return $this->state(fn () => [
            'provider' => SocialAccountEnum::Threads->value,
        ]);
    }
}
