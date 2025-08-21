<?php

namespace App\Http\Controllers\Api\v1\Ideas;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Syllaby\Ideas\Keyword;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\KeywordResource;
use App\Syllaby\Ideas\Actions\ListKeywordHistoryAction;

class KeywordHistoryController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Retrieve and display a list of the user's recent search history.
     */
    public function index(Request $request, ListKeywordHistoryAction $history): JsonResponse
    {
        $keywords = $history->handle($this->user(), $this->take(5), $request->has('metrics'));

        return $this->respondWithPagination(KeywordResource::collection($keywords));
    }

    /**
     * Deletes the given keyword from the user search history.
     */
    public function destroy(Keyword $keyword): Response
    {
        $this->authorize('delete', $keyword);

        $this->user()->keywords()->detach($keyword->id);

        return response()->noContent();
    }
}
