<?php

namespace App\Http\Controllers\Api\v1\Channels;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\SocialAccountResource;
use App\Http\Requests\Social\SocialRefreshRequest;
use App\Syllaby\Publisher\Channels\Actions\RefreshAction;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

class SocialRefreshController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function update(SocialRefreshRequest $request, string $provider, RefreshAction $action): JsonResponse
    {
        try {
            return $this->respondWithResource(
                SocialAccountResource::make($action->handle($request->validated(), SocialAccountEnum::fromString($provider)))
            );
        } catch (InvalidRefreshTokenException) {
            return $this->errorInternalError(__('social.refresh_failed', ['provider' => $provider]));
        }
    }
}
