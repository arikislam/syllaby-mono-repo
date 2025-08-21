<?php

namespace App\Syllaby\Clonables\Vendors\Avatars;

use Illuminate\Support\Str;
use App\Syllaby\Clonables\Clonable;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use App\Syllaby\Clonables\Enums\CloneStatus;
use Illuminate\Http\Client\RequestException;
use App\Syllaby\RealClones\Enums\RealCloneProvider;
use App\Syllaby\Clonables\Contracts\UserCloneContract;

class FastVideo implements UserCloneContract
{
    /**
     * Triggers the user clone model for training.
     *
     * @throws RequestException
     */
    public function clone(Clonable $clonable, string $source): UserCloneData
    {
        $response = $this->http()->post('/trainModel', [
            'version' => 'v1',
            'video_url' => $source,
            'appID' => config('services.fastvideo.app_id'),
            'webhook' => route('fastvideo.webhook:clone', ['clonable_id' => $clonable->id]),
        ]);

        if ($response->failed()) {
            $response->throw();
        }

        return new UserCloneData(
            provider: RealCloneProvider::FASTVIDEO->value,
            provider_id: $response->json('model_id'),
            status: CloneStatus::REVIEWING,
        );
    }

    /**
     * Configure HTTP client to interact with FastVideo API.
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
