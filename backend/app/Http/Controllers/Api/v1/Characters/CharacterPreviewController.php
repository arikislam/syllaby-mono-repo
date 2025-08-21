<?php

namespace App\Http\Controllers\Api\v1\Characters;

use App\Http\Controllers\Controller;
use App\Syllaby\Characters\Character;
use App\Http\Resources\CharacterResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Characters\CreateCharacterPreviewRequest;
use App\Syllaby\Characters\Actions\CreateCharacterPreviewAction;

class CharacterPreviewController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(CreateCharacterPreviewRequest $request, Character $character, CreateCharacterPreviewAction $action)
    {
        $character = $action->handle($character, $request->validated());

        return $this->respondWithResource(CharacterResource::make($character), Response::HTTP_CREATED);
    }
}
