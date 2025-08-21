<?php

namespace App\Http\Controllers\Api\v1\Channels;

use Log;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\SocialAccountResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Social\SocialCallbackRequest;
use App\Syllaby\Publisher\Channels\Actions\CallbackAction;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Exceptions\ChannelNotFoundException;

class SocialCallbackController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function create(SocialCallbackRequest $request, string $provider, CallbackAction $action): JsonResponse
    {
        try {
            $account = $action->handle(SocialAccountEnum::fromString($provider));
            $account->load('channels.account');

            return $this->respondWithResource(new SocialAccountResource($account), Response::HTTP_CREATED);
        } catch (ChannelNotFoundException $exception) {
            return $this->errorInternalError($exception->getMessage());
        } catch (Exception $e) {
            Log::alert('Error while receiving callback', [$e->getMessage()]);

            return $this->errorInternalError($e->getMessage());
        }
    }
}
