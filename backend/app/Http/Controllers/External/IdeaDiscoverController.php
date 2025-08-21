<?php

namespace App\Http\Controllers\External;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Syllaby\Ideas\Keyword;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Ideas\Enums\Networks;
use App\Http\Resources\KeywordResource;
use App\Syllaby\Ideas\Contracts\IdeaDiscovery;

class IdeaDiscoverController extends Controller
{
    /**
     * Discover ideas based on a given keyword.
     */
    public function store(Request $request, IdeaDiscovery $discover): JsonResponse
    {
        if (! $this->hasToken($request)) {
            return $this->errorUnauthorized();
        }

        $input = $request->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:80'],
            'network' => ['required', 'string', Rule::in(Networks::toArray())],
        ]);

        $user = User::find(170419);

        $term = Arr::get($input, 'keyword');
        $input = Arr::except($input, 'keyword');

        if (! $keyword = $this->fetchKeyword($term, Arr::get($input, 'network'))) {
            $keyword = $discover->search($term, $input, $user);
        }

        $keyword->users()->syncWithPivotValues(
            ids: [$user->id],
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

    private function hasToken(Request $request): bool
    {
        $token = config('auth.external.token');
        $authorization = $request->header('Authorization');

        return filled($authorization) && $authorization === "Bearer {$token}";
    }
}
