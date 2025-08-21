<?php

namespace App\Http\Controllers\Api\v1\UserFeedback;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserFeedbackResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\UserFeedback\UserFeedbackRequest;
use App\Syllaby\Surveys\Actions\CreateUserFeedbackAction;

class UserFeedbackController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(UserFeedbackRequest $request, CreateUserFeedbackAction $feedback): JsonResponse
    {
        if (!$result = $feedback->handle($this->user(), $request->validated())) {
            return $this->errorInternalError('Whoops! It was not possible to save your feedback.');
        }

        return $this->respondWithResource(UserFeedbackResource::make($result), Response::HTTP_CREATED);
    }
}
