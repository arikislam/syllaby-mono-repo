<?php

namespace App\Http\Controllers\Api\v1\Characters;

use DB;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Characters\Character;
use App\Http\Resources\CharacterResource;
use App\Syllaby\Characters\Actions\TrainCharacterAction;
use App\Http\Requests\Characters\StartCharacterTrainingRequest;

class CharacterTrainingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(StartCharacterTrainingRequest $request, Character $character, TrainCharacterAction $action): JsonResponse
    {
        $character = DB::transaction(function () use ($character, $request, $action) {
            return $action->handle($character, $request->validated());
        });

        return $this->respondWithResource(new CharacterResource($character));
    }
}
