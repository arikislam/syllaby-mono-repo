<?php

namespace App\Syllaby\Publisher\Publications\Concerns;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Publications\DTOs\FacebookVideoData;
use App\Syllaby\Publisher\Publications\Jobs\LogPublicationsJob;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Publications\Notifications\PublicationSuccessful;
use App\Syllaby\Publisher\Publications\Exceptions\PublicationFailedException;

/**
 * @see https://developers.facebook.com/docs/marketing-api/error-reference for Error codes
 */
trait PublishesFacebookMedia
{
    public function publishReel(Publication $publication, SocialChannel $channel): Publication
    {
        $dto = $this->getDto($publication, $channel);

        $initResponse = Http::meta()->asJson()->post("{$channel->provider_id}/video_reels", [
            'upload_phase' => 'start',
            'access_token' => $channel->access_token,
        ]);

        if ($initResponse->failed() && $initResponse->json('error.code') == 200) {
            $channel->account()->update(['needs_reauth' => true]);
            $this->fail($publication, $channel, __('publish.lost_permission'), $initResponse->json());
            throw PublicationFailedException::permissionRequired();
        }

        if ($initResponse->json('video_id') === null) {
            $this->fail($publication, $channel, __('publish.generic_error'), $initResponse->json());
            throw new PublicationFailedException(__('publish.generic_error'));
        }

        $videoId = $initResponse->json('video_id');

        $this->uploadMediaToFacebook($channel, $publication, $initResponse);

        $finalResponse = Http::meta()->withQueryParameters([
            'access_token' => $channel->access_token,
            'video_id' => $videoId,
            'description' => $dto->caption,
            'upload_phase' => 'finish',
            'video_state' => 'PUBLISHED',
        ])->post("/{$channel->provider_id}/video_reels");

        if ($finalResponse->failed() && $finalResponse->json('error.code') == 100) {
            $this->fail($publication, $channel, __('publish.generic_error'), $finalResponse->json());
            throw new PublicationFailedException(__('publish.generic_error'));
        }

        if ($finalResponse->json('error.code') == 6000 && $finalResponse->json('error.error_subcode') == 1363130) {
            $this->fail($publication, $channel, __('publish.malformed_media'), $finalResponse->json());
            throw PublicationFailedException::malformedMedia();
        }

        dispatch(new LogPublicationsJob($publication, $channel, $finalResponse->json()));

        return tap($publication, function ($publication) use ($finalResponse, $channel) {
            $this->success($publication, $channel, $finalResponse->json('post_id'));
        });
    }

    public function publishPost(Publication $publication, SocialChannel $channel): Publication
    {
        $dto = $this->getDto($publication, $channel);

        $params = [
            'access_token' => $channel->access_token,
            'file_url' => $publication->asset()->getUrl(),
            'description' => $dto->caption,
            'title' => $dto->title,
        ];

        if ($this->hasThumbnail($publication)) {
            $params['thumb'] = fopen($publication->thumbnail(SocialAccountEnum::Facebook)->getUrl(), 'r');
        }

        $uploadResponse = Http::meta()->asForm()->post("/{$channel->provider_id}/videos", $params);

        if ($uploadResponse->failed() && $uploadResponse->json('error.code') == 6000 && $uploadResponse->json('error.error_subcode') == 1363042) {
            $channel->account()->update(['needs_reauth' => true]);
            $this->fail($publication, $channel, __('publish.lost_permission'), $uploadResponse->json());
            throw PublicationFailedException::permissionRequired();
        }

        if ($uploadResponse->failed() && $uploadResponse->json('error.code') == 389 && $uploadResponse->json('error.error_subcode') == 1363057) {
            $this->fail($publication, $channel, __('publish.malformed_media'), $uploadResponse->json());
            throw PublicationFailedException::malformedMedia();
        }

        if ($uploadResponse->failed() || $uploadResponse->json('id') === null) {
            $this->fail($publication, $channel, __('publish.generic_error'), $uploadResponse->json());
            throw new PublicationFailedException(__('publish.generic_error'));
        }

        dispatch(new LogPublicationsJob($publication, $channel, $uploadResponse->json()));

        return tap($publication, function ($publication) use ($uploadResponse, $channel) {
            $this->success($publication, $channel, (string) $uploadResponse->json('id'));
        });
    }

    public function publishStory(Publication $publication, SocialChannel $channel): Publication
    {
        $dto = $this->getDto($publication, $channel);

        $initResponse = Http::meta()->post("{$channel->provider_id}/video_stories", [
            'upload_phase' => 'start',
            'access_token' => $channel->access_token,
        ]);

        if ($initResponse->failed() && $initResponse->json('error.code') == 6000 && $initResponse->json('error.error_subcode') == 1363042) {
            $channel->account()->update(['needs_reauth' => true]);
            $this->fail($publication, $channel, __('publish.lost_permission'), $initResponse->json());
            throw PublicationFailedException::permissionRequired();
        }

        if ($initResponse->json('video_id') === null) {
            $this->fail($publication, $channel, __('publish.generic_error'), $initResponse->json());
            throw new PublicationFailedException(__('publish.generic_error'));
        }

        $videoId = $initResponse->json('video_id');

        $this->uploadMediaToFacebook($channel, $publication, $initResponse);

        $finalResponse = Http::meta()->post("{$channel->provider_id}/video_stories", [
            'upload_phase' => 'finish',
            'video_id' => $videoId,
            'access_token' => $channel->access_token,
            'description' => $dto->caption,
        ]);

        if ($finalResponse->failed() || $finalResponse->json('post_id') === null) {
            $this->fail($publication, $channel, __('publish.generic_error'), $finalResponse->json());
            throw new PublicationFailedException(__('publish.generic_error'));
        }

        dispatch(new LogPublicationsJob($publication, $channel, $finalResponse->json()));

        return tap($publication, function ($publication) use ($finalResponse, $channel) {
            $this->success($publication, $channel, $finalResponse->json('post_id'));
        });
    }

    private function getDto(Publication $publication, SocialChannel $channel): FacebookVideoData
    {
        $data = $publication->channels()->wherePivot('social_channel_id', $channel->id)->first();

        return FacebookVideoData::fromArray($data->pivot->metadata);
    }

    private function success(Publication $publication, SocialChannel $channel, string $id): void
    {
        $publication->channels()->updateExistingPivot($channel, [
            'status' => SocialUploadStatus::COMPLETED->value,
            'provider_media_id' => $id,
        ]);

        $publication->user->notify(new PublicationSuccessful($publication, $channel));
    }

    private function fail(Publication $publication, SocialChannel $channel, string $message = 'Something went wrong', array $payload = []): void
    {
        Log::alert("Facebook Publication Failed -  publication [{$publication->id}] on channel [{$channel->id}]", compact('payload'));

        dispatch(new LogPublicationsJob($publication, $channel, $payload));

        $publication->channels()->updateExistingPivot($channel, [
            'status' => SocialUploadStatus::FAILED->value,
            'error_message' => $message,
        ]);
    }

    private function uploadMediaToFacebook(SocialChannel $channel, Publication $publication, Response $initResponse): void
    {
        $uploadResponse = Http::withHeader('Authorization', "OAuth {$channel->access_token}")
            ->withHeader('file_url', $publication->asset()->getUrl())
            ->post($initResponse->json('upload_url'));

        if ($uploadResponse->failed() && $uploadResponse->json('debug_info.type') == 'ProcessingFailedError') {
            $this->fail($publication, $channel, __('publish.malformed_media'), $uploadResponse->json());
            throw PublicationFailedException::malformedMedia();
        }
    }
}
