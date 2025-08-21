<?php

namespace App\Syllaby\RealClones\Vendors;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Speeches\Speech;
use Illuminate\Http\UploadedFile;
use App\Syllaby\RealClones\Avatar;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Syllaby\RealClones\RealClone;
use App\Syllaby\Credits\CreditHistory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use Illuminate\Http\Client\ConnectionException;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use App\Syllaby\RealClones\Enums\RealCloneProvider;
use App\Syllaby\RealClones\Contracts\PresenterContract;
use App\Syllaby\RealClones\Contracts\FaceDetectorContract;

class DiD implements FaceDetectorContract, PresenterContract
{
    /**
     * Generates a Real Clone video.
     *
     * @throws RequestException|ConnectionException
     */
    public function generate(RealClone $clone, Speech $speech): RealClone
    {
        $response = match ($clone->avatar->type) {
            Avatar::PHOTO => $this->http()->post('/talks', $this->talksPayload($clone, $speech)),
            Avatar::REAL_CLONE_LITE => $this->http()->post('/scenes', $this->scenesPayload($clone, $speech)),
            default => $this->http()->post('/clips', $this->clipsPayload($clone, $speech))
        };

        if ($response->failed()) {
            $response->throw();
        }

        return tap($clone)->update([
            'url' => null,
            'synced_at' => null,
            'hash' => $clone->hashes(),
            'provider_id' => $response->json('id'),
            'status' => RealCloneStatus::GENERATING,
        ]);
    }

    /**
     * Calculate and charge the user credits.
     */
    public function charge(RealClone $clone): void
    {
        (new CreditService($clone->user))->decrement(
            type: CreditEventEnum::REAL_CLONE_GENERATED,
            creditable: $clone,
            amount: real_clone_credits($clone->provider, $clone->script),
            label: Str::limit($clone->script, CreditHistory::TRUNCATED_LENGTH)
        );
    }

    /**
     * Fetch and saves in storage the allowed avatars.
     *
     * @throws RequestException|ConnectionException
     */
    public function avatars(array $allowed): void
    {
        $response = $this->http()->get('/clips/presenters?limit=100');

        if ($response->failed()) {
            $response->throw(fn () => Log::error('Error while fetching D-ID avatars.'));
        }

        collect($response->json('presenters'))
            ->filter(fn ($avatar) => Arr::exists($allowed, $avatar['presenter_id']))
            ->map(fn ($avatar) => [...$avatar, 'name' => $allowed[$avatar['presenter_id']]])
            ->each(fn ($avatar) => $this->persistAvatar($avatar));
    }

    /**
     * Detects whether a face is present within provided image.
     *
     * @throws RequestException|ConnectionException
     */
    public function detectFaces(UploadedFile $image): ?array
    {
        $response = $this->http()->attach('image', $image->getContent(), $image->hashName(), [
            'Content-Type' => $image->getMimeType(),
        ])->post('images', ['detect_faces' => 'true']);

        if ($response->failed()) {
            $response->throw();
        }

        $this->http()->delete("images/{$response->json('id')}");

        if (blank($response->json('faces'))) {
            return null;
        }

        return $response->json('faces')[0];
    }

    /**
     * Payload to generate a real clone.
     */
    private function clipsPayload(RealClone $clone, Speech $speech): array
    {
        return [
            'background' => ['color' => false],
            'presenter_id' => $clone->avatar->provider_id,
            'config' => ['logo' => false, 'result_format' => 'webm'],
            'script' => [
                'ssml' => false,
                'type' => 'audio',
                'subtitles' => false,
                'audio_url' => $speech->getFirstMediaUrl('script'),
            ],
            'webhook' => url()->query(config('services.d-id.webhook_url'), [
                'type' => Avatar::REAL_CLONE,
            ]),
        ];
    }

    /**
     * Payload to create a real clone lite video.
     */
    private function scenesPayload(RealClone $clone, Speech $speech): array
    {
        return [
            'persist' => false,
            'avatar_id' => $clone->avatar->provider_id,
            'script' => [
                'type' => 'audio',
                'subtitles' => false,
                'audio_url' => $speech->getFirstMediaUrl('script'),
            ],
            'webhook' => url()->query(config('services.d-id.webhook_url'), [
                'type' => Avatar::REAL_CLONE_LITE,
            ]),
        ];
    }

    /**
     * Payload to create an animation type of video.
     */
    private function talksPayload(RealClone $clone, Speech $speech): array
    {
        $avatar = $clone->avatar;

        $payload = [
            'source_url' => $avatar->preview_url,
            'script' => [
                'type' => 'audio',
                'subtitles' => false,
                'reduce_noise' => true,
                'audio_url' => $speech->getFirstMediaUrl('script'),
            ],
            'config' => [
                'logo' => false,
                'stitch' => true,
                'fluent' => 'false',
                'pad_audio' => '0.0',
                'result_format' => 'mp4',
            ],
            'webhook' => url()->query(config('services.d-id.webhook_url'), [
                'type' => Avatar::PHOTO,
            ]),
        ];

        if (blank(Arr::get($avatar->metadata, 'face'))) {
            return $payload;
        }

        return array_merge($payload, ['face' => [
            'size' => Arr::get($avatar->metadata, 'face.size'),
            'face_id' => Arr::get($avatar->metadata, 'face.face_id'),
            'overlap' => Arr::get($avatar->metadata, 'face.overlap'),
            'top_left' => Arr::get($avatar->metadata, 'face.top_left'),
            'detection' => Arr::get($avatar->metadata, 'face.detection'),
            'detect_confidence' => Arr::get($avatar->metadata, 'face.detect_confidence'),
            'face_occluded_confidence' => Arr::get($avatar->metadata, 'face.face_occluded_confidence'),
        ]]);
    }

    /**
     * Saves in storage the given avatar details.
     */
    private function persistAvatar(array $avatar): void
    {
        $lookup = [
            'provider' => RealCloneProvider::D_ID->value,
            'provider_id' => Arr::get($avatar, 'presenter_id'),
        ];

        $attributes = [
            'is_active' => true,
            'type' => Avatar::STANDARD,
            'name' => Arr::get($avatar, 'name'),
            'gender' => Arr::get($avatar, 'gender'),
            'preview_url' => Arr::get($avatar, 'image_url'),
            'metadata' => Arr::only($avatar, ['owner_id', 'model_url', 'driver_id']),
        ];

        Avatar::updateOrCreate($lookup, $attributes);
    }

    /**
     * Http client to interact with D-ID API.
     */
    private function http(): PendingRequest
    {
        return Http::acceptJson()
            ->baseUrl(config('services.d-id.url'))
            ->withHeaders(['Authorization' => 'Basic '.config('services.d-id.key')]);
    }
}
