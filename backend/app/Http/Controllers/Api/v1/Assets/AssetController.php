<?php

namespace App\Http\Controllers\Api\v1\Assets;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Characters\Genre;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\AssetResource;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Assets\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;
use App\Syllaby\Assets\Jobs\DeleteBulkAssetsJob;
use App\Http\Requests\Assets\BulkDeleteAssetsRequest;

class AssetController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * List, search and filter assets.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $filters = $request->query('filter', []);

        $assets = Asset::search($request->query('query'))
            ->where('user_id', $user->id)
            ->query(fn ($query) => $this->withEloquent($query, $user, $filters))
            ->when(Arr::get($filters, 'status'), fn ($query, $status) => $query->where('status', $status))
            ->when(Arr::get($filters, 'type'), fn ($query, $type) => $query->whereIn('type', explode(',', $type)))
            ->when(Arr::get($filters, 'orientation'), fn ($query, $orientation) => $query->where('orientation', $orientation))
            ->when(Arr::get($filters, 'genre'), function ($query, $genre) {
                $identifiers = Genre::whereIn('slug', collect(explode(',', $genre)))->pluck('id');
                return $query->whereIn('genre_id', $identifiers);
            })
            ->latest('id')
            ->paginate(20)->withQueryString();

        return $this->respondWithPagination(AssetResource::collection($assets));
    }

    /**
     * Display the specified asset.
     */
    public function show(Request $request, Asset $asset): JsonResponse
    {
        $this->authorize('view', $asset);

        $query = QueryBuilder::for(Asset::class)
            ->allowedIncludes(['genre', 'user', 'videos'])
            ->where('id', $asset->id)
            ->where('user_id', $request->user()->id)
            ->with('media')
            ->withCount('videos')
            ->withExists(['bookmarks as is_bookmarked' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->first();

        return $this->respondWithResource(AssetResource::make($query));
    }

    /**
     * Update the specified asset.
     */
    public function update(Request $request, Asset $asset): JsonResponse
    {
        $this->authorize('update', $asset);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $asset->update($validated);

        return $this->respondWithResource(AssetResource::make($asset->load('media')));
    }

    public function destroy(Asset $asset)
    {
        $this->authorize('delete', $asset);

        if ($asset->videos()->exists()) {
            return $this->errorWrongArgs('Cannot delete an asset that is actively used in the video.');
        }

        if ($asset->status->is(AssetStatus::PROCESSING)) {
            return $this->errorWrongArgs('Cannot delete an asset that is currently processing.');
        }

        $asset->delete();

        return response()->noContent();
    }

    public function bulkDestroy(BulkDeleteAssetsRequest $request): JsonResponse
    {
        $assets = $request->validated('assets');

        dispatch(new DeleteBulkAssetsJob($assets, $this->user()));

        return $this->respondWithMessage('Assets have been queued for deletion.', Response::HTTP_ACCEPTED);
    }

    private function withEloquent(Builder $query, User $user, array $filters): Builder
    {
        return $query->withExists(['bookmarks as is_bookmarked' => fn ($q) => $q->where('user_id', $user->id)])
            ->when(Arr::get($filters, 'bookmarked'), fn ($query) => $query->whereHas('bookmarks'))
            ->where('type', '!=', AssetType::AUDIOS->value)
            ->with(['media'])
            ->withCount('videos');
    }
}
