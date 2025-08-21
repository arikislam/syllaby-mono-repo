<?php

namespace App\Http\Controllers\Api\v1\Ideas;

use Illuminate\Support\Str;
use App\Syllaby\Ideas\Keyword;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Ideas\Enums\Networks;
use App\Http\Resources\KeywordResource;
use App\Syllaby\Ideas\Contracts\IdeaDiscovery;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Http\Requests\Ideas\IdeaDiscoverRequest;

class IdeaDiscoverController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Discover ideas based on a given keyword.
     */
    public function store(IdeaDiscoverRequest $request, IdeaDiscovery $discover): JsonResponse
    {
        $term = $request->validated('keyword');
        $input = $request->safe()->except('keyword');

        if (!$keyword = $this->fetchKeyword($term, $request->validated('network'))) {
            $keyword = $discover->search($term, $input, $this->user());
        }

        if ($keyword->ideas()->count() > 0) {
            $this->charge($keyword);
        }

        $keyword->users()->syncWithPivotValues(
            ids: [$this->user()->id],
            values: ['updated_at' => now()],
            detaching: false
        );

        return $this->respondWithResource(KeywordResource::make($keyword));
    }

    /**
     * Fetch the keyword from storage if exists.
     */
    private function fetchKeyword(string $keyword, string $network): ?Keyword
    {
        return Keyword::where('updated_at', '>=', now()->subWeeks(3))
            ->where('source', '<>', 'openai')
            ->where('slug', Str::slug($keyword))
            ->where('network', Networks::from($network))
            ->first();
    }

    /**
     * Charge user credits for searching a keyword.
     */
    private function charge(Keyword $keyword): void
    {
        (new CreditService($this->user()))->decrement(
            type: CreditEventEnum::IDEA_DISCOVERED,
            creditable: $keyword,
            label: $keyword->name
        );
    }
}
