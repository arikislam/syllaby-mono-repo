<?php

namespace App\Syllaby\Publisher\Publications\Vendors;

use Override;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Sleep;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Promise\PromiseInterface;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Publications\DTOs\ThreadsVideoData;
use App\Syllaby\Publisher\Publications\Jobs\LogPublicationsJob;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Publications\Concerns\ValidatesMetaMedia;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;
use App\Syllaby\Publisher\Publications\Notifications\PublicationSuccessful;
use App\Syllaby\Publisher\Publications\Exceptions\PublicationFailedException;

class ThreadsProvider extends AbstractProvider
{
    use ValidatesMetaMedia;

    const string TYPE_VIDEO = 'VIDEO';

    public function publish(Publication $publication, SocialChannel $channel, PostType $type = PostType::POST): Publication
    {
        $data = $publication->channels()->wherePivot('social_channel_id', $channel->id)->first();

        /** @var ThreadsVideoData $dto */
        $dto = ThreadsVideoData::fromArray($data->pivot->metadata);

        $response = Http::post("https://graph.threads.net/v1.0/{$channel->provider_id}/threads", [
            'media_type' => self::TYPE_VIDEO,
            'video_url' => $publication->asset()->getUrl(),
            'text' => $dto->caption,
            'access_token' => $channel->account->access_token,
        ]);

        if ($this->needsPermission($response)) {
            $channel->account()->update(['needs_reauth' => true]);
            $this->fail($publication, $channel, $response->json('error.message'), $response->json());
            throw PublicationFailedException::permissionRequired();
        }

        if ($response->failed()) {
            $this->fail($publication, $channel, $response->json('error.message'), $response->json());
            throw new PublicationFailedException(__('publish.generic_error'));
        }

        $id = $response->json('id');

        $attempts = 0;

        Sleep::for(25)->seconds();

        while ($attempts < 4) {
            $response = Http::post("https://graph.threads.net/v1.0/{$channel->provider_id}/threads_publish", [
                'access_token' => $channel->account->access_token,
                'creation_id' => $id,
            ]);

            if ($response->successful()) {
                $postId = $response->json('id');
                break;
            }

            $attempts++;
            Sleep::for(10)->seconds();
        }

        if ($response->failed() || ! isset($postId)) {
            $this->fail($publication, $channel, $response->json('error.message'), $response->json());
            throw new PublicationFailedException(__('publish.generic_error'));
        }

        dispatch(new LogPublicationsJob($publication, $channel, $response->json()));

        return tap($publication, function ($publication) use ($channel, $postId) {
            $this->success($publication, $channel, $postId);
        });
    }

    public function valid(Publication $publication, PostType $type = PostType::POST): bool
    {
        $media = $publication->asset();

        return $this->isThreadsPost($media);
    }

    public function provider(): SocialAccountEnum
    {
        return SocialAccountEnum::Threads;
    }

    #[Override]
    public function prepare(Publication $publication, SocialChannel $channel, array $data, PostType $type = PostType::POST): Publication
    {
        $expiry = CarbonImmutable::parse($channel->account->updated_at->timestamp + $channel->account->expires_in);

        if ($expiry->isPast()) {
            $channel->account->update(['needs_reauth' => true]);
            throw new InvalidRefreshTokenException(__('social.refresh_failed', ['provider' => $this->provider()->toString()]));
        }

        return tap($publication, function (Publication $publication) use ($channel, $data, $type) {
            $publication->update(['draft' => false, 'temporary' => false, 'scheduled' => Arr::has($data, 'scheduled_at')]);
            $publication->channels()->sync($this->format($data, $channel, $type), false);
        });
    }

    public function format(array $data, SocialChannel $channel, PostType $type = PostType::POST): array
    {
        return [
            $channel->id => [
                'metadata' => ThreadsVideoData::fromArray($data)->toArray(),
                'status' => Arr::has($data, 'scheduled_at') ? SocialUploadStatus::SCHEDULED->value : SocialUploadStatus::PROCESSING->value,
                'post_type' => $type->value,
            ],
        ];
    }

    private function fail(Publication $publication, SocialChannel $channel, string $message = 'Something went wrong', array $payload = []): void
    {
        Log::alert("Threads Publication Failed -  publication [{$publication->id}] on channel [{$channel->id}]", compact('payload'));

        dispatch(new LogPublicationsJob($publication, $channel, $payload));

        $publication->channels()->updateExistingPivot($channel, [
            'status' => SocialUploadStatus::FAILED->value,
            'error_message' => $message,
        ]);
    }

    private function success(Publication $publication, SocialChannel $channel, string $id): void
    {
        $publication->channels()->updateExistingPivot($channel, [
            'status' => SocialUploadStatus::COMPLETED->value,
            'provider_media_id' => $id,
        ]);

        $channel->user->notify(new PublicationSuccessful($publication, $channel));
    }

    private function needsPermission(PromiseInterface|Response $response): bool
    {
        return ($response->json('error.type') === 'THApiException' && $response->json('error.code') === 10)
            || ($response->json('error.type') === 'OAuthException' && $response->json('error.code') === 190);
    }
}
