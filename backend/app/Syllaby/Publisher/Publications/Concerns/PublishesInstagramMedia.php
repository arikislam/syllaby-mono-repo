<?php

namespace App\Syllaby\Publisher\Publications\Concerns;

use Illuminate\Support\Sleep;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Promise\PromiseInterface;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\DTOs\InstagramVideoData;
use App\Syllaby\Publisher\Publications\Jobs\LogPublicationsJob;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Publications\Notifications\PublicationSuccessful;
use App\Syllaby\Publisher\Publications\Exceptions\PublicationFailedException;

/**
 * @see https://developers.facebook.com/docs/instagram-api/guides/content-publishing/
 */
trait PublishesInstagramMedia
{
    public function publishReel(Publication $publication, SocialChannel $channel): Publication
    {
        $dto = $this->getDto($publication, $channel);

        $createContainer = Http::meta()->post("{$channel->provider_id}/media", [
            'media_type' => 'REELS',
            'video_url' => $publication->asset()->getUrl(),
            'caption' => $dto->caption,
            'share_to_feed' => $dto->share_to_feed,
            'access_token' => $channel->account->access_token,
        ]);

        if ($createContainer->json('error.code') == 190 && $createContainer->json('error.error_subcode') == 460) {
            $channel->account()->update(['needs_reauth' => true]);
            $this->fail($publication, $channel, __('publish.lost_permission'), $createContainer->json());
            throw new PublicationFailedException(__('publish.lost_permission'));
        }

        if ($createContainer->failed() || $createContainer->json('id') === null) {
            $this->fail($publication, $channel, __('publish.generic_error'), $createContainer->json());
            throw new PublicationFailedException(__('publish.generic_error'));
        }

        return $this->publishAndSaveMedia($dto, $createContainer->json('id'), $channel, $publication);
    }

    public function publishStory(Publication $publication, SocialChannel $channel): Publication
    {
        $dto = $this->getDto($publication, $channel);

        $createContainer = Http::meta()->post("{$channel->provider_id}/media", [
            'media_type' => 'STORIES',
            'video_url' => $publication->asset()->getUrl(),
            'access_token' => $channel->account->access_token,
        ]);

        /**
         * We cant publish a story on instagram creator account. This account needs to be a business one.
         */
        if ($createContainer->failed() && $createContainer->json('error.code') == 10) {
            $this->fail($publication, $channel, __('publish.incompatible_account'), $createContainer->json());
            throw new PublicationFailedException(__('publish.incompatible_account'));
        }

        if ($createContainer->failed() || $createContainer->json('id') === null) {
            $this->fail($publication, $channel, __('publish.generic_error'), $createContainer->json());
            throw new PublicationFailedException(__('publish.generic_error'));
        }

        return $this->publishAndSaveMedia($dto, $createContainer->json('id'), $channel, $publication);
    }

    private function publishAndSaveMedia(InstagramVideoData $dto, string $containerId, SocialChannel $channel, Publication $publication): mixed
    {
        $metadata = $dto->setVideoId($containerId)->toArray();

        $this->ensureMediaIsPublished($containerId, $channel, $publication);

        $response = $this->publishContainer($publication, $channel, $containerId);

        dispatch(new LogPublicationsJob($publication, $channel, $response->json()));

        return tap($publication, function ($publication) use ($response, $channel, $metadata) {
            $this->success($publication, $channel, $metadata, (string) $response->json('id'));
        });
    }

    private function getDto(Publication $publication, SocialChannel $channel): InstagramVideoData
    {
        $data = $publication->channels()->wherePivot('social_channel_id', $channel->id)->first();

        return InstagramVideoData::fromArray($data->pivot->metadata);
    }

    private function fail(Publication $publication, SocialChannel $channel, string $message = 'Something went wrong', array $payload = []): void
    {
        Log::alert("Instagram Publication Failed -  publication [{$publication->id}] on channel [{$channel->id}]", compact('payload'));

        dispatch(new LogPublicationsJob($publication, $channel, $payload));

        $publication->channels()->updateExistingPivot($channel, [
            'status' => SocialUploadStatus::FAILED->value,
            'error_message' => $message,
        ]);
    }

    private function success(Publication $publication, SocialChannel $channel, array $metadata, string $id): void
    {
        $publication->channels()->updateExistingPivot($channel, [
            'status' => SocialUploadStatus::COMPLETED->value,
            'provider_media_id' => $id,
            'metadata' => $metadata,
        ]);

        $channel->user->notify(new PublicationSuccessful($publication, $channel));
    }

    private function ensureMediaIsPublished(mixed $containerId, SocialChannel $channel, Publication $publication): void
    {
        $statusRequest = $this->checkStatusRequest($containerId, $channel);

        if ($statusRequest->json('status_code') === 'FINISHED') {
            return;
        }

        if ($statusRequest->json('status_code') === 'ERROR' || $statusRequest->failed()) {
            $this->fail($publication, $channel, __('publish.malformed_media'), $statusRequest->json());
            throw new PublicationFailedException(__('publish.malformed_media'));
        }

        $attempts = 0;
        Sleep::for(10)->seconds();

        /**
         * We can't make the next publishing request until this is in "FINISHED" state.
         *
         * @see https://developers.facebook.com/docs/instagram-api/reference/ig-user/media#creating
         * @see https://developers.facebook.com/docs/instagram-api/guides/content-publishing
         */
        while ($attempts < 30) {
            $statusRequest = $this->checkStatusRequest($containerId, $channel);

            if ($statusRequest->json('status_code') === 'FINISHED') {
                break;
            }

            $attempts++;
            Sleep::for(5)->seconds();
        }
    }

    private function checkStatusRequest(string $containerId, SocialChannel $channel): Response|PromiseInterface
    {
        return Http::meta()->get($containerId, [
            'fields' => 'status_code',
            'access_token' => $channel->account->access_token,
        ]);
    }

    private function publishContainer(Publication $publication, SocialChannel $channel, mixed $containerId): Response|PromiseInterface
    {
        $response = Http::meta()->post("{$channel->provider_id}/media_publish", [
            'creation_id' => $containerId,
            'access_token' => $channel->account->access_token,
        ]);

        if ($response->failed()) {
            $this->fail($publication, $channel, __('publish.generic_error'), $response->json());
            throw new PublicationFailedException(__('publish.generic_error'));
        }

        return $response;
    }
}
