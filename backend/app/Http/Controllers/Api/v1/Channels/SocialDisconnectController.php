<?php

namespace App\Http\Controllers\Api\v1\Channels;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Social\SocialDisconnectRequest;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Actions\DisconnectAction;

class SocialDisconnectController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function update(SocialDisconnectRequest $request, string $provider, DisconnectAction $action): JsonResponse
    {
        try {
            $action->handle($request->validated(), SocialAccountEnum::fromString($provider));

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (Exception) {
            return $this->errorInternalError(__('social.disconnect_failed', ['provider' => $provider]));
        }
    }
}
