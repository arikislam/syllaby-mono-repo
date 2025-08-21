<?php

namespace App\Syllaby\Folders\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Folders\Folder;
use App\Syllaby\Folders\Resource;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Contracts\Pagination\Paginator;

class FetchFoldersAction
{
    /**
     * Handles the fetching of folders.
     */
    public function handle(Resource $resource, User $user, array $parameters): Paginator
    {
        $query = Folder::whereHas('resource', function ($query) use ($resource) {
            $query->where('parent_id', $resource->id);
        });

        $folders = QueryBuilder::for($query)
            ->defaultSort(Arr::get($this->sorts(), 'default'))
            ->allowedSorts(Arr::get($this->sorts(), 'allowed'))
            ->with(['resource' => fn ($query) => $query->withCount('children')])
            ->withExists(['bookmarks as is_bookmarked' => fn ($q) => $q->where('user_id', $user->id)])
            ->simplePaginate(Arr::get($parameters, 'per_page', 30))
            ->withQueryString();

        return $folders->through(function ($folder) {
            return tap($folder->resource, fn ($resource) => $resource->setRelation('model', $folder));
        });
    }

    /**
     * Gets the sorts for the folders.
     */
    protected function sorts(): array
    {
        return [
            'default' => [
                AllowedSort::field('date', 'id')->defaultDirection('desc'),
            ],
            'allowed' => [
                AllowedSort::field('name'),
                AllowedSort::field('date', 'id'),
                AllowedSort::field('bookmarked', 'is_bookmarked'),
            ],
        ];
    }
}
