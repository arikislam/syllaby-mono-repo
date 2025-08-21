<?php

namespace Database\Factories;

use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Syllaby\Publisher\Publications\AccountPublication;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;

/** @extends Factory<AccountPublication> */
class AccountPublicationFactory extends Factory
{
    protected $model = AccountPublication::class;

    public function definition(): array
    {
        return [
            'social_channel_id' => SocialChannel::factory(),
            'publication_id' => Publication::factory(),
            'metadata' => [],
            'provider_media_id' => $this->faker->uuid,
            'status' => SocialUploadStatus::COMPLETED
        ];
    }

    public function completed(): self
    {
        return $this->state(fn() => [
            'status' => SocialUploadStatus::COMPLETED
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn() => [
            'status' => SocialUploadStatus::FAILED
        ]);
    }

    public function processing(): self
    {
        return $this->state(fn() => [
            'status' => SocialUploadStatus::PROCESSING
        ]);
    }

    public function scheduled(): self
    {
        return $this->state(fn() => [
            'status' => SocialUploadStatus::SCHEDULED
        ]);
    }

    public function draft(): self
    {
        return $this->state(fn() => [
            'status' => SocialUploadStatus::DRAFT
        ]);
    }
}
