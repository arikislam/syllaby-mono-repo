<?php

namespace App\Http\Controllers\Api\v1\User;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Syllaby\Users\Actions\DeleteUserAction;
use App\Http\Requests\User\UpdateProfileRequest;

class UserProfileController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function show(): JsonResponse
    {
        return $this->respondWithResource(UserResource::make($this->user()));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->user();

        $user->fill($request->validated());

        if (! $user->save()) {
            return $this->errorInternalError('Whoops! Something went wrong. Please try again later.');
        }

        return $this->respondWithResource(UserResource::make($user));
    }

    public function seenWelcomeMessage(): JsonResponse
    {
        $user = $this->user();

        $userSettings = $user->settings;
        $userSettings['welcome_message'] = true;
        $user->settings = $userSettings;

        if (! $user->save()) {
            return $this->errorInternalError('Whoops! Something went wrong. Please try again later.');
        }

        return $this->respondWithResource(UserResource::make($user));
    }

    /**
     * Delete the user's account.
     */
    public function destroy(DeleteUserAction $remove): JsonResponse
    {
        $remove->handle($this->user());

        return $this->respondWithMessage('Your account has been set to be deleted.');
    }
}
