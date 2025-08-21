<?php

namespace App\Http\Controllers\Api\v1\Authentication;

use Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Auth\Actions\LoginAction;
use App\Http\Resources\UserWithTokenResource;
use App\Syllaby\Auth\Actions\RegistrationAction;
use App\Http\Requests\Authentication\LoginRequest;
use App\Http\Requests\Authentication\RegistrationRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthenticationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth:sanctum')->only('logout');
    }

    public function login(LoginRequest $request, LoginAction $action): UserWithTokenResource|JsonResponse
    {
        $request->ensureIsThrottled();

        if (! $user = $action->handle($request->validated())) {
            return $this->errorInternalError('Whoops! Something went wrong. Please try again');
        }

        return $this->respondWithResource(new UserWithTokenResource($user));
    }

    public function register(RegistrationRequest $request, RegistrationAction $action): UserWithTokenResource|JsonResponse
    {
        if (! $user = $action->handle($request->validated())) {
            return $this->errorInternalError('Whoops! Something went wrong. Please try again');
        }

        return $this->respondWithResource(new UserWithTokenResource($user), SymfonyResponse::HTTP_CREATED);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
