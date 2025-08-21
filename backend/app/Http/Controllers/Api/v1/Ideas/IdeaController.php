<?php

namespace App\Http\Controllers\Api\v1\Ideas;

use App\Syllaby\Ideas\Keyword;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\IdeaResource;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class IdeaController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Display a paginated list of ideas for the given keyword
     */
    public function index(): JsonResponse
    {
        /** @var Keyword $keyword */
        $keyword = QueryBuilder::for($this->user()->keywords())->allowedFilters([
            AllowedFilter::exact('keyword', 'slug'),
            AllowedFilter::exact('network'),
        ])->first();

        if (blank($keyword)) {
            return $this->errorForbidden("You haven't previously searched for this keyword");
        }

        $ideas = QueryBuilder::for($keyword->ideas())
            ->defaultSort('-volume')
            ->allowedSorts(['cpc', 'volume', 'competition', 'trend'])
            ->paginate($this->take())
            ->withQueryString();

        return $this->respondWithPagination(IdeaResource::collection($ideas));
    }
}
