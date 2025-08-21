<?php

namespace App\Http\Controllers\Api\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Syllaby\Users\Actions\UpdatePasswordAction;

class UserPasswordController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function update(UpdatePasswordRequest $request, UpdatePasswordAction $action)
    {
        $user = $action->handle($request->validated());

        if (!$user) {
            return $this->errorInternalError('Whoops! Something went wrong. Please try again later.');
        }

        return $this->respondWithResource(new UserResource($user));
    }
}
