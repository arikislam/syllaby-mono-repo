<?php

namespace App\Http\Controllers\Api\v1\Channels;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\SocialAccountResource;
use App\Http\Requests\Social\StoreChannelRequest;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Actions\ShowChannelAction;
use App\Syllaby\Publisher\Channels\Actions\StoreChannelAction;

class SocialChannelController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function index(string $provider, ShowChannelAction $action): JsonResponse
    {
        try {
            return $this->respondWithArray($action->handle(SocialAccountEnum::fromString($provider)));
        } catch (Throwable $exception) {
            return $this->errorInternalError($exception->getMessage());
        }
    }

    public function store(StoreChannelRequest $request, string $provider, StoreChannelAction $action): JsonResponse
    {
        try {
            $account = $action->handle(SocialAccountEnum::fromString($provider), $request->validated());
            return $this->respondWithResource(SocialAccountResource::make($account), Response::HTTP_CREATED);
        } catch (Throwable) {
            return $this->errorInternalError(__('social.failed', ['provider' => $provider]));
        }
    }
}
