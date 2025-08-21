<?php

namespace App\Syllaby\Publisher\Publications\Services\LinkedIn;

use Exception;
use RuntimeException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Assets\Media;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\PendingRequest;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Publications\DTOs\LinkedInVideoData;

class UploadService
{
    public function __construct(protected Publication $publication, protected SocialChannel $channel) {}

    /**
     * @throws Exception
     */
    public function upload(): string
    {
        $registerUpload = $this->registerUpload();

        if (! $asset = $this->publication->asset()) {
            throw new Exception('Unable to find video to post');
        }

        $videoChunkIds = $this->uploadInChunks($registerUpload, $asset);

        $this->finalizeMedia($asset, $registerUpload, $videoChunkIds);
        $this->uploadThumbnail($registerUpload);
        $postRequest = $this->syncMediaAndPost($registerUpload);

        return $postRequest->header('x-restli-id');
    }

    public function urn(): string
    {
        return match ($this->channel->type) {
            SocialChannel::INDIVIDUAL => "urn:li:person:{$this->channel->provider_id}",
            SocialChannel::ORGANIZATION => "urn:li:organization:{$this->channel->provider_id}",
        };
    }

    /**
     * @throws RuntimeException
     */
    private function uploadInChunks(PromiseInterface|Response $registerUpload, Media $asset): array
    {
        $instructions = $registerUpload->json('value.uploadInstructions');
        $path = $this->getTemporaryPath($asset);

        if (! $stream = Storage::disk('local')->readStream($path)) {
            throw new RuntimeException("Unable to read video file from path: [{$path}]");
        }

        $headers = collect($instructions)->map(function ($instruction) use ($stream, $asset) {
            $url = Arr::get($instruction, 'uploadUrl');
            $start = Arr::get($instruction, 'firstByte');
            $end = Arr::get($instruction, 'lastByte');

            fseek($stream, $start);
            $chunk = fread($stream, ($end - $start) + 1);

            $uploadRequest = $this->http()
                ->retry(5, fn () => app()->runningUnitTests() ? 0 : 3000)
                ->withBody($chunk, $asset->mime_type)
                ->put($url);

            return $uploadRequest->header('etag');
        });

        Storage::disk('local')->deleteDirectory(dirname($path));

        return $headers->toArray();
    }

    private function thumbnailExists(): bool
    {
        return $this->publication->thumbnail(SocialAccountEnum::LinkedIn) !== null;
    }

    private function fetchPostData(): array
    {
        return $this->publication->channels()
            ->wherePivot('social_channel_id', $this->channel->id)
            ->first()->pivot->metadata;
    }

    private function uploadThumbnail(PromiseInterface|Response $registerUpload): void
    {
        if (! $this->thumbnailExists()) {
            return;
        }

        $thumbnail = $this->publication->thumbnail(SocialAccountEnum::LinkedIn);

        $this->http()
            ->withHeader('media-type-family', 'STILLIMAGE')
            ->withBody(file_get_contents($thumbnail->getUrl()), $thumbnail->mime_type)
            ->put($registerUpload->json('value.thumbnailUploadUrl'));
    }

    private function finalizeMedia(Media $asset, PromiseInterface|Response $registerUpload, array $videoChunkIds): void
    {
        Http::withHeader('X-Restli-Protocol-Version', config('services.linkedin.protocol_version'))
            ->withHeader('LinkedIn-Version', config('services.linkedin.api_version'))
            ->withToken($this->channel->account->access_token)
            ->contentType($asset->mime_type)->throw()
            ->post('https://api.linkedin.com/rest/videos?action=finalizeUpload', $this->finalMediaPayload($registerUpload, $videoChunkIds));
    }

    private function registerUpload(): Response|PromiseInterface
    {
        return $this->http()->post('https://api.linkedin.com/rest/videos?action=initializeUpload', $this->registerMediaPayload());
    }

    private function syncMediaAndPost(PromiseInterface|Response $registerUpload): Response|PromiseInterface
    {
        return $this->http()->asJson()->post('https://api.linkedin.com/rest/posts', $this->postPayload($registerUpload));
    }

    private function finalMediaPayload(PromiseInterface|Response $registerUpload, array $videoChunkIds): array
    {
        return [
            'finalizeUploadRequest' => [
                'video' => $registerUpload->json('value.video'),
                'uploadToken' => '',
                'uploadedPartIds' => $videoChunkIds,
            ],
        ];
    }

    private function postPayload(PromiseInterface|Response $registerUpload): array
    {
        $data = LinkedInVideoData::fromArray($this->fetchPostData());

        return [
            'author' => $this->urn(),
            'commentary' => $data->caption ?? '',
            'visibility' => $this->channel->isOrganization() ? 'PUBLIC' : $data->visibility, // Organization posts can only be public
            'distribution' => [
                'feedDistribution' => 'MAIN_FEED',
                'targetEntities' => [],
                'thirdPartyDistributionChannels' => [],
            ],
            'content' => [
                'media' => [
                    'title' => $data->title ?? '',
                    'id' => $registerUpload->json('value.video'),
                ],
            ],
            'lifecycleState' => 'PUBLISHED',
            'isReshareDisabledByAuthor' => false,
        ];
    }

    private function registerMediaPayload(): array
    {
        return [
            'initializeUploadRequest' => [
                'owner' => $this->urn(),
                'fileSizeBytes' => $this->publication->asset()->size,
                'uploadCaptions' => false,
                'uploadThumbnail' => $this->thumbnailExists(),
            ],
        ];
    }

    private function getTemporaryPath(Media $media): string
    {
        $uuid = Str::uuid();
        $base = "tmp/uploads/{$uuid}/{$media->getDownloadFilename()}";

        if (Storage::disk('local')->missing(dirname($base))) {
            Storage::disk('local')->makeDirectory(dirname($base));
        }

        $path = Storage::disk('local')->path($base);
        Http::timeout(600)->retry(3, 1000)->sink($path)->get($media->getUrl());

        return $base;
    }

    private function http(): PendingRequest
    {
        return Http::throw()->withHeaders([
            'X-Restli-Protocol-Version' => config('services.linkedin.protocol_version'),
            'LinkedIn-Version' => config('services.linkedin.api_version'),
        ])->withToken($this->channel->account->access_token);
    }
}
