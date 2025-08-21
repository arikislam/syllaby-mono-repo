<?php

namespace App\Http\Controllers\Api\v1\Clones;

use Illuminate\Http\JsonResponse;
use App\Syllaby\Clonables\Clonable;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClonableResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Clones\CreateVoiceCloneRequest;
use App\Http\Requests\Clones\UpdateVoiceCloneRequest;
use App\Syllaby\Clonables\Actions\CreateVoiceCloneAction;
use App\Syllaby\Clonables\Actions\UpdateVoiceCloneAction;

class VoiceCloneController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Create in storage a voice clone intent for the user.
     */
    public function store(CreateVoiceCloneRequest $request, CreateVoiceCloneAction $create): JsonResponse
    {
        $clone = $create->handle($this->user(), $request->validated());
        $clone->load(['media', 'model']);

        return $this->respondWithResource(ClonableResource::make($clone), Response::HTTP_CREATED);
    }

    /**
     * Updates a given voice clone intent.
     */
    public function update(Clonable $clonable, UpdateVoiceCloneRequest $request, UpdateVoiceCloneAction $update): JsonResponse
    {
        if (! $clone = $update->handle($clonable, $request->validated())) {
            return $this->errorInternalError('Whoops! It was not possible to update a voice clone intent.');
        }

        return $this->respondWithResource(ClonableResource::make($clone));
    }
}
