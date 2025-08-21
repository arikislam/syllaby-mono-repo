<?php

namespace App\Syllaby\RealClones\Vendors;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Speeches\Speech;
use App\Syllaby\RealClones\Avatar;
use Illuminate\Support\Facades\Log;
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

class Heygen implements PresenterContract
{
    /**
     * Generates a Real Clone video.
     *
     * @throws RequestException
     */
    public function generate(RealClone $clone, Speech $speech): RealClone
    {
        $response = $this->http()->post('/video.webm', $this->videoPayload($clone, $speech));

        if ($response->failed()) {
            $response->throw();
        }

        return tap($clone)->update([
            'url' => null,
            'synced_at' => null,
            'hash' => $clone->hashes(),
            'status' => RealCloneStatus::GENERATING,
            'provider_id' => $response->json('data.video_id'),
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
     * @throws RequestException
     */
    public function avatars(array $allowed): void
    {
        $response = $this->http()->get('/avatar.list');

        if ($response->failed()) {
            $response->throw(fn () => Log::error('Error while fetching HeyGen avatars.'));
        }

        collect($response->json('data.avatars.*.avatar_states'))
            ->flatMap(fn ($state) => $state)
            ->filter(fn ($avatar) => in_array($avatar['id'], $allowed))
            ->each(fn ($avatar) => $this->persistAvatar($avatar));
    }

    /**
     * Payload to start generating a real clone.
     */
    private function videoPayload(RealClone $clone, Speech $speech): array
    {
        return [
            'avatar_style' => 'normal',
            'avatar_pose_id' => $clone->avatar->provider_id,
            'input_audio' => $speech->getFirstMediaUrl('script'),
        ];
    }

    /**
     * Saves in storage the given avatar details.
     */
    private function persistAvatar(array $avatar): void
    {
        $lookup = [
            'provider_id' => Arr::get($avatar, 'id'),
            'provider' => RealCloneProvider::HEYGEN->value,
        ];

        $attributes = [
            'is_active' => true,
            'type' => Avatar::STANDARD,
            'name' => Arr::get($avatar, 'name'),
            'gender' => Arr::get($avatar, 'gender'),
            'preview_url' => Arr::get($avatar, 'normal_preview'),
        ];

        Avatar::updateOrCreate($lookup, $attributes);
    }

    /**
     * Http client to interact with HeyGen API.
     */
    private function http(): PendingRequest
    {
        return Http::acceptJson()
            ->baseUrl(config('services.heygen.url'))
            ->withHeaders(['X-Api-Key' => config('services.heygen.key')]);
    }
}
