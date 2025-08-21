<?php

namespace App\Syllaby\RealClones\Vendors;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use App\Syllaby\Speeches\Speech;
use App\Syllaby\RealClones\Avatar;
use Illuminate\Support\Facades\Http;
use App\Syllaby\RealClones\RealClone;
use App\Syllaby\Credits\CreditHistory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use App\Syllaby\RealClones\Enums\RealCloneProvider;
use App\Syllaby\RealClones\Contracts\PresenterContract;
use App\Syllaby\RealClones\Services\CreateAvatarPreview;

class FastVideo implements PresenterContract
{
    /**
     * Generates a Real Clone video.
     *
     * @throws RequestException
     */
    public function generate(RealClone $clone, Speech $speech): RealClone
    {
        $response = $this->http()->post('/createVideo', [
            'format' => 'mp4',
            'model_id' => $clone->avatar->provider_id,
            'appID' => config('services.fastvideo.app_id'),
            'webhook' => route('fastvideo.webhook:avatar'),
            'audio_url' => $speech->getFirstMediaUrl('script'),
        ]);

        if ($response->failed()) {
            $response->throw();
        }

        return tap($clone)->update([
            'url' => null,
            'synced_at' => null,
            'hash' => $clone->hashes(),
            'status' => RealCloneStatus::GENERATING,
            'provider_id' => $response->json('video_id'),
        ]);
    }

    /**
     * Calculate and charge the user credits for video generation.
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
     * @throws RequestException
     */
    public function avatars(array $allowed): void
    {
        $response = $this->http()->post('/getModels', [
            'status' => 'finished',
            'appID' => config('services.fastvideo.app_id'),
        ]);

        if ($response->failed()) {
            $response->throw();
        }

        $ids = array_keys($allowed);

        collect($response->json('models'))
            ->filter(fn ($avatar) => in_array($ids, $avatar['modelID']))
            ->map(fn ($avatar) => [...$avatar, ...Arr::get($allowed, $avatar['modelID'])])
            ->each(fn ($avatar) => $this->persistAvatar($avatar));
    }

    /**
     * Saves in storage the given avatar details.
     */
    private function persistAvatar(array $data): void
    {
        $lookup = [
            'provider_id' => Arr::get($data, 'modelID'),
            'provider' => RealCloneProvider::FASTVIDEO->value,
        ];

        $attributes = [
            'metadata' => [],
            'is_active' => true,
            'type' => Avatar::STANDARD,
            'name' => Arr::get($data, 'name'),
            'gender' => Arr::get($data, 'gender'),
        ];

        tap(Avatar::updateOrCreate($lookup, $attributes), function ($avatar) use ($data) {
            CreateAvatarPreview::from(Arr::get($data, 'videoURL'), $avatar);
        });
    }

    /**
     * Http client to interact with FastVideo API.
     */
    private function http(): PendingRequest
    {
        return Http::acceptJson()
            ->baseUrl(config('services.fastvideo.url'))
            ->withHeaders([
                'X-RapidAPI-Key' => config('services.fastvideo.rapid_api_key'),
                'X-RapidAPI-Host' => Str::after(config('services.fastvideo.url'), 'https://'),
            ]);
    }
}
