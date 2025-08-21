<?php

namespace App\Http\Controllers\Api\v1\Editor;

use Cache;
use App\Syllaby\Users\User;
use Illuminate\Http\Request;
use App\Syllaby\Editor\EditorAsset;
use App\Http\Controllers\Controller;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Resources\EditorAssetResource;

class EditorAssetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $key = $this->getCacheKeyForPagination($request, $this->user());

        $query = EditorAsset::query()->ownedBy($this->user());

        $assets = Cache::remember($key, now()->addHours(12), function () use ($query) {
            return QueryBuilder::for($query)->allowedFilters(['type'])->paginate($this->take());
        });

        return $this->respondWithPagination(EditorAssetResource::collection($assets));
    }

    private function getCacheKeyForPagination(Request $request, User $user): string
    {
        return sprintf(
            'editor-assets:page-%s:per_page-%s:type-%s:user-%s',
            $request->query('page', 1),
            $request->query('per_page', 10),
            $request->input('filter.type', 'all'),
            $user->id
        );
    }
}