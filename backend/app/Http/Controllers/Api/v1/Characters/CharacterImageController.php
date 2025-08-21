<?php

namespace App\Http\Controllers\Api\v1\Characters;

use Arr;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use App\Syllaby\Characters\Character;
use App\Syllaby\Assets\Actions\UploadMediaAction;
use App\Http\Requests\UpdateCharacterImageRequest;

class CharacterImageController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function update(UpdateCharacterImageRequest $request, Character $character, UploadMediaAction $action): JsonResponse
    {
        $media = $action->handle($character, Arr::wrap($request->image), 'reference');

        return $this->respondWithResource(new MediaResource(Arr::first($media)));
    }
}
