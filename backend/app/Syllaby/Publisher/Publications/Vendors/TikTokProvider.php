<?php

namespace App\Syllaby\Publisher\Publications\Vendors;

use Throwable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Publications\DTOs\TikTokVideoData;
use App\Syllaby\Publisher\Publications\Jobs\LogPublicationsJob;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Publications\Exceptions\PublicationFailedException;

class TikTokProvider extends AbstractProvider
{
    const string TIKTOK_URL = 'https://open.tiktokapis.com/v2/post/publish/video/init/';

    const array VALID_MIMES = ['video/mp4', 'video/webm', 'video/quicktime'];

    public function publish(Publication $publication, SocialChannel $channel, PostType $type = PostType::POST): Publication
    {
        $data = $publication->channels()->wherePivot('social_channel_id', $channel->id)->first();
        $dto = TikTokVideoData::fromArray($data->pivot->metadata);

        try {
            $response = $this->http($channel->account->access_token)->post(
                self::TIKTOK_URL, $this->bodyParams($dto, $publication)
            );
        } catch (Throwable $exception) {
            Log::alert('TikTok Publication Failed at request level', [
                'publication' => $publication->id,  'channel' => $channel->id, 'message' => $exception->getMessage(),
            ]);

            throw $exception;
        } finally {
            dispatch(new LogPublicationsJob($publication, $channel, $response->json()));
        }

        if ($response->json('error.code') != 'ok') {
            $this->fail($publication, $channel, $response->json('error.message'));
            throw new PublicationFailedException($response->json('error.message'));
        }

        $metadata = $dto->setPublishId($response->json('data.publish_id'))->toArray();

        return tap($publication, function ($publication) use ($channel, $metadata) {
            $this->process($publication, $channel, $metadata);
        });
    }

    public function valid(Publication $publication, PostType $type = PostType::POST): bool
    {
        if (! $media = $publication->asset()) {
            return false;
        }

        return in_array($media->mime_type, self::VALID_MIMES);
    }

    public function provider(): SocialAccountEnum
    {
        return SocialAccountEnum::TikTok;
    }

    private function bodyParams(TikTokVideoData $dto, Publication $publication): array
    {
        $description = trim(preg_replace('/\\\\[nrt]/', '', preg_replace('/[[:cntrl:]]/', '', $dto->caption)));

        return [
            'post_info' => [
                'title' => $description,
                'privacy_level' => $dto->privacy_status,
                'disable_duet' => ! $dto->allow_duet,
                'disable_stitch' => ! $dto->allow_stitch,
                'disable_comment' => ! $dto->allow_comments,
            ],
            'source_info' => [
                'source' => 'PULL_FROM_URL',
                'video_url' => $publication->asset()->getUrl(),
            ],
        ];
    }

    private function fail(Publication $publication, SocialChannel $channel, string $reason = 'Something went wrong'): void
    {
        $publication->channels()->updateExistingPivot($channel, [
            'error_message' => $reason,
            'status' => SocialUploadStatus::FAILED->value,
        ]);
    }

    private function process(Publication $publication, SocialChannel $channel, array $metadata): void
    {
        $publication->channels()->updateExistingPivot($channel, [
            'status' => SocialUploadStatus::PROCESSING->value,
            'metadata' => $metadata,
        ]);
    }

    public function format(array $data, SocialChannel $channel, PostType $type = PostType::POST): array
    {
        return [
            $channel->id => [
                'metadata' => TikTokVideoData::fromArray($data)->toArray(),
                'status' => Arr::has($data, 'scheduled_at') ? SocialUploadStatus::SCHEDULED->value : SocialUploadStatus::PROCESSING->value,
                'post_type' => $type->value,
            ],
        ];
    }

    private function http(string $token): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }
}
