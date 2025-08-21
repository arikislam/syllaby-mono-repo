<?php

namespace App\Http\Controllers\Api\v1\Clones;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClonableResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Clones\CreateAvatarCloneRequest;
use App\Syllaby\Clonables\Actions\CreateAvatarCloneAction;

class AvatarCloneController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Create in storage an avatar clone intent for the user.
     */
    public function store(CreateAvatarCloneRequest $request, CreateAvatarCloneAction $create): JsonResponse
    {
        $clonable = $create->handle($this->user(), $request->validated());

        return $this->respondWithResource(ClonableResource::make($clonable), Response::HTTP_CREATED);
    }
}
