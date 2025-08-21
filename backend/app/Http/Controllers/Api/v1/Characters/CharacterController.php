<?php

namespace App\Http\Controllers\Api\v1\Characters;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Characters\Character;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Resources\CharacterResource;
use App\Syllaby\Characters\Enums\CharacterStatus;
use App\Http\Requests\Characters\CreateCharacterRequest;
use App\Http\Requests\Characters\UpdateCharacterRequest;
use App\Syllaby\Characters\Filter\CustomCharacterFilter;
use App\Syllaby\Characters\Actions\CreateCharacterAction;
use App\Syllaby\Characters\Actions\UpdateCharacterAction;
use App\Syllaby\Characters\Jobs\DeleteCustomCharacterJob;

class CharacterController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Get all the characters.
     */
    public function index(Request $request): JsonResponse
    {
        $characters = QueryBuilder::for(Character::class)
            ->allowedFilters([
                AllowedFilter::exact('genre', 'genre.slug'),
                AllowedFilter::custom('type', new CustomCharacterFilter($this->user()))->default('system'),
            ])
            ->whereNotIn('status', [CharacterStatus::DRAFT, CharacterStatus::PREVIEW_FAILED, CharacterStatus::PREVIEW_READY])
            ->with(['genre' => fn ($query) => $query->active()])
            ->active()
            ->get();

        return $this->respondWithResource(CharacterResource::collection($characters));
    }

    public function store(CreateCharacterRequest $request, CreateCharacterAction $action): JsonResponse
    {
        $character = $action->handle($this->user(), $request->validated());

        return $this->respondWithResource(new CharacterResource($character->refresh()), Response::HTTP_CREATED);
    }

    public function show(Character $character): JsonResponse
    {
        $this->authorize('show', $character);

        return $this->respondWithResource(new CharacterResource($character->load('genre')));
    }

    public function update(UpdateCharacterRequest $request, Character $character, UpdateCharacterAction $action): JsonResponse
    {
        $character = $action->handle($character, $request->validated());

        return $this->respondWithResource(new CharacterResource($character->load('genre')));
    }

    public function destroy(Character $character): Response
    {
        $this->authorize('destroy', $character);

        dispatch_sync(new DeleteCustomCharacterJob($character));

        return response()->noContent();
    }
}
