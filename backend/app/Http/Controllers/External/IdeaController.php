<?php

namespace App\Http\Controllers\External;

use App\Syllaby\Users\User;
use Illuminate\Http\Request;
use App\Syllaby\Ideas\Keyword;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\IdeaResource;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class IdeaController extends Controller
{
    /**
     * Display a paginated list of ideas for the given keyword
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->hasToken($request)) {
            return $this->errorUnauthorized();
        }

        $user = User::find(170419);

        /** @var Keyword $keyword */
        $keyword = QueryBuilder::for($user->keywords())->allowedFilters([
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

    private function hasToken(Request $request): bool
    {
        $token = config('auth.external.token');
        $authorization = $request->header('Authorization');

        return filled($authorization) && $authorization === "Bearer {$token}";
    }
}
