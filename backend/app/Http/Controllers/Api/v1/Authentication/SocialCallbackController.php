<?php

namespace App\Http\Controllers\Api\v1\Authentication;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Auth\Actions\CallbackAction;
use App\Http\Resources\UserWithTokenResource;
use Symfony\Component\HttpFoundation\Response;
use App\Syllaby\Auth\Exceptions\EmailAlreadyExists;
use App\Http\Requests\Authentication\SocialLoginRequest;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

class SocialCallbackController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Handle the social login callback.
     */
    public function create(SocialLoginRequest $request, string $provider, CallbackAction $action): JsonResponse
    {
        try {
            $user = $action->handle(SocialAccountEnum::fromString($provider), $request->query('token'));

            return $this->respondWithResource(new UserWithTokenResource($user));
        } catch (EmailAlreadyExists $e) {
            return $this->errorWrongArgs($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $exception) {
            return $this->errorInternalError($exception->getMessage());
        }
    }
}
