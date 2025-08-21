<?php

namespace App\Http\Controllers\Api\v1\Ideas;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\IdeaResource;
use App\Syllaby\Ideas\Actions\IdeasSuggestionsAction;

class IdeaSuggestionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a list of ideas suggestions based on industries.
     */
    public function index(Request $request, IdeasSuggestionsAction $suggestions): JsonResponse
    {
        $industry = Arr::get($request->query('filter'), 'industry', 'all');
        $ideas = $suggestions->handle($industry, $this->take(), $request->has('sort'));

        return $this->respondWithPagination(IdeaResource::collection($ideas));
    }
}
