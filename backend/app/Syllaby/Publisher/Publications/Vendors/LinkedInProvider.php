<?php

namespace App\Syllaby\Publisher\Publications\Vendors;

use Illuminate\Support\Arr;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Publications\DTOs\LinkedInVideoData;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Publications\Jobs\LinkedInPublicationJob;

class LinkedInProvider extends AbstractProvider
{
    const int FILE_SIZE = 200 * 1024 * 1024; // 200MB

    public function publish(Publication $publication, SocialChannel $channel, PostType $type = PostType::POST): Publication
    {
        return tap($publication, function () use ($publication, $channel) {
            dispatch(new LinkedInPublicationJob($publication, $channel));
        });
    }

    public function valid(Publication $publication, PostType $type = PostType::POST): bool
    {
        $media = $publication->asset();

        return $media->size <= self::FILE_SIZE;
    }

    public function provider(): SocialAccountEnum
    {
        return SocialAccountEnum::LinkedIn;
    }

    public function format(array $data, SocialChannel $channel, PostType $type = PostType::POST): array
    {
        return [
            $channel->id => [
                'metadata' => LinkedInVideoData::fromArray($data)->toArray(),
                'status' => Arr::has($data, 'scheduled_at') ? SocialUploadStatus::SCHEDULED->value : SocialUploadStatus::PROCESSING->value,
                'post_type' => $type->value,
            ],
        ];
    }
}
