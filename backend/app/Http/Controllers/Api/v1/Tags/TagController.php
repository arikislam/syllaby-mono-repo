<?php

namespace App\Http\Controllers\Api\v1\Tags;

use App\Syllaby\Tags\Tag;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\TagResource;
use App\Http\Controllers\Controller;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Syllaby\Tags\Filters\TagsTypeFilter;

class TagController extends Controller
{
    /**
     * Allowed relationships includes
     */
    protected array $includes = [
        'templates', 'user', 'media',
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a list of filtered tags.
     */
    public function index(): JsonResponse
    {
        $user = $this->user();

        $tags = QueryBuilder::for(Tag::class)->allowedFilters([
            AllowedFilter::exact('name'),
            AllowedFilter::custom('media', new TagsTypeFilter($user)),
            AllowedFilter::custom('templates', new TagsTypeFilter($user)),
        ])
            ->ownedBy($user)
            ->orderByRaw('user_id IS NULL')
            ->orderBy('user_id')
            ->allowedIncludes($this->includes)
            ->paginate($this->take(50));

        return $this->respondWithPagination(TagResource::collection($tags));
    }

    /**
     * Display the tag with the given id.
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->user();

        $tag = QueryBuilder::for(Tag::class)->allowedFilters([
            AllowedFilter::custom('media', new TagsTypeFilter($user)),
            AllowedFilter::custom('templates', new TagsTypeFilter($user)),
        ])
            ->allowedIncludes($this->includes)
            ->where('id', $id)
            ->ownedBy($user)
            ->first();

        if (blank($tag)) {
            return $this->respondWithArray(null);
        }

        return $this->respondWithResource(TagResource::make($tag));
    }
}
