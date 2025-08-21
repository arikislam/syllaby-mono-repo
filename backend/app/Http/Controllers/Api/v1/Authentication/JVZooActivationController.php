<?php

namespace App\Http\Controllers\Api\v1\Authentication;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Syllaby\Auth\Actions\JVZooActivationAction;
use App\Http\Requests\Authentication\JVZooActivationRequest;

class JVZooActivationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Activate JVZoo account by setting password.
     *
     * @throws Throwable|ValidationException
     */
    public function store(JVZooActivationRequest $request, JVZooActivationAction $action): JsonResponse
    {
        $response = $action->handle($request->validated());

        return $this->respondWithMessage($response);
    }
}
