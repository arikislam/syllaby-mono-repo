<?php

namespace App\Http\Controllers\Api\v1\Thumbnails;

use Exception;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Asset;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\AssetResource;
use Spatie\QueryBuilder\QueryBuilder;
use App\Syllaby\Assets\Enums\AssetType;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Assets\CreateThumbnailRequest;
use App\Syllaby\Assets\Actions\CreateThumbnailAction;
use App\Syllaby\Credits\Actions\ChargeImageGenerationAction;

class ThumbnailController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('subscribed')->only('store');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        if (Feature::inactive('thumbnails')) {
            return $this->errorUnsupportedFeature();
        }

        $user = $this->user();

        $assets = QueryBuilder::for(Asset::class)
            ->allowedIncludes('media')
            ->where('user_id', $user->id)
            ->where('type', AssetType::THUMBNAIL)
            ->latest('id')
            ->paginate($this->take());

        return $this->respondWithPagination(AssetResource::collection($assets));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateThumbnailRequest $request, CreateThumbnailAction $generate, ChargeImageGenerationAction $charge): JsonResponse
    {
        if (Feature::inactive('thumbnails')) {
            return $this->errorUnsupportedFeature();
        }

        try {
            $user = $this->user();

            $thumbnails = $generate->handle($user, $request->validated());
            $charge->handle($user, null, 1, 'Thumbnail Generation');

            return $this->respondWithResource(AssetResource::collection($thumbnails));
        } catch (Exception $exception) {
            return $this->errorInternalError($exception->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Asset $thumbnail): Response
    {
        $this->authorize('delete', $thumbnail);

        $thumbnail->delete();

        return response()->noContent();
    }
}
