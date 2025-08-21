<?php

namespace App\Syllaby\Publisher\Publications\Vendors;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Publications\DTOs\YoutubeVideoData;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Publications\Jobs\YoutubePublicationJob;

class YoutubeProvider extends AbstractProvider
{
    public function publish(Publication $publication, SocialChannel $channel, PostType $type = PostType::POST): Publication
    {
        dispatch(new YoutubePublicationJob($publication, $channel));

        return $publication;
    }

    public function valid(Publication $publication, PostType $type = PostType::POST): bool
    {
        $media = $publication->asset();

        return Str::startsWith($media->mime_type, 'video/') || $media->mime_type == 'application/octet-stream';
    }

    public function provider(): SocialAccountEnum
    {
        return SocialAccountEnum::Youtube;
    }

    public function format(array $data, SocialChannel $channel, PostType $type = PostType::POST): array
    {
        return [
            $channel->id => [
                'metadata' => YoutubeVideoData::fromArray($data)->toArray(),
                'status' => Arr::has($data, 'scheduled_at') ? SocialUploadStatus::SCHEDULED->value : SocialUploadStatus::PROCESSING->value,
                'post_type' => $type->value,
            ],
        ];
    }
}
