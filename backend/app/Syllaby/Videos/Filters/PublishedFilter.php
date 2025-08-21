<?php

namespace App\Syllaby\Videos\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class PublishedFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $value
            ? $query->whereHas('publications', fn ($query) => $query->where('draft', false)->where('temporary', false))
            : $query->doesntHave('publications');
    }
}
