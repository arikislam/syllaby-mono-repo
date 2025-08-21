<?php

namespace App\Http\Controllers\Api\v1\Authentication;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Syllaby\Auth\Actions\ResetPasswordAction;
use App\Syllaby\Auth\Actions\ForgetPasswordAction;
use App\Http\Requests\Authentication\ResetPasswordRequest;
use App\Http\Requests\Authentication\ForgotPasswordRequest;

class PasswordRecoveryController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /** @throws Throwable */
    public function forgetPassword(ForgotPasswordRequest $request, ForgetPasswordAction $action): JsonResponse
    {
        $response = $action->handle($request->validated());

        return $this->respondWithMessage(__($response));
    }

    /** @throws Throwable|ValidationException */
    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        $response = $action->handle($request->validated());

        return $this->respondWithMessage(__($response));
    }
}
