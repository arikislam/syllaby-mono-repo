<?php

namespace App\Http\Controllers\Api\v1\SocialMedia;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Shared\TikTok\TikTok;
use App\Shared\Facebook\Pixel;
use App\Http\Controllers\Controller;

class TrackEventController extends Controller
{
    /**
     * Track server side events for facebook and tiktok.
     */
    public function store(Request $request)
    {
        if (! app()->isProduction()) {
            return $this->respondWithMessage('Not tracking');
        }

        $input = $request->validate([
            'id' => ['required', 'uuid'],
            'event' => ['required', 'string'],
            'data' => ['sometimes', 'nullable', 'array'],
        ]);

        $id = Arr::get($input, 'id');
        $event = Arr::get($input, 'event');
        $data = Arr::get($input, 'data');

        Pixel::track($id, $event, $data);
        TikTok::track($id, $event, $data);

        if ($event === 'Signup') {
            $this->storeAdTracking($data);
        }

        return $this->respondWithMessage('Success');
    }

    /**^
     * Store ad tracking data for the user.
     */
    private function storeAdTracking(array $data): void
    {
        if (! $user = User::find(Arr::get($data, 'user.id'))) {
            return;
        }

        $tracking = array_filter([
            'tiktok' => collect(Arr::get($data, 'cookies.tiktok', []))->pluck('value', 'key')->filter()->all(),
            'facebook' => collect(Arr::get($data, 'cookies.facebook', []))->pluck('value', 'key')->filter()->all(),
        ]);

        if (empty($tracking)) {
            return;
        }

        $user->update(['ad_tracking' => $tracking]);
    }
}
