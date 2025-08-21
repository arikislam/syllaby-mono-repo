<?php

namespace App\Syllaby\Videos\Filters;

use Illuminate\Support\Arr;
use App\Syllaby\Characters\Genre;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class GenreFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $identifiers = Genre::whereIn('slug', Arr::wrap($value))->pluck('id');

        $query->whereHas('faceless', function ($q) use ($identifiers) {
            $q->whereIn('genre_id', $identifiers);
        });
    }
}
