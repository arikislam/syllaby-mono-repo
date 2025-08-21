<?php

namespace App\Http\Controllers\Api\v1\Ideas;

use Exception;
use App\Syllaby\Ideas\Topic;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Resources\RelatedTopicResource;
use App\Http\Requests\Ideas\RelatedTopicRequest;
use App\Syllaby\Ideas\Actions\ManageRelatedTopicAction;

class RelatedTopicController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
        $this->middleware(['subscribed'])->only('store');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $user = $this->user();

        $topic = QueryBuilder::for(Topic::class)->where('user_id', $user->id)
            ->withExists(['bookmarks as is_bookmarked' => fn ($q) => $q->where('user_id', $user->id)])
            ->allowedFilters(AllowedFilter::callback('bookmarked', function ($query, $value) use ($user) {
                $value ? $query->whereHas('bookmarks', fn ($q) => $q->where('user_id', $user->id)) : null;
            }))
            ->inRandomOrder()
            ->first();

        if (blank($topic)) {
            $topic = Topic::where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))->first();
        }

        return $this->respondWithResource(RelatedTopicResource::make($topic));
    }

    /**
     * Store a new related topic.
     */
    public function store(RelatedTopicRequest $request, ManageRelatedTopicAction $suggestion): JsonResponse
    {
        try {
            $topic = $suggestion->handle($this->user(), $request->validated());
        } catch (Exception $exception) {
            return $this->errorInternalError($exception->getMessage());
        }

        return $this->respondWithResource(RelatedTopicResource::make($topic));
    }
}
