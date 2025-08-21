<?php

namespace App\Syllaby\Publisher\Publications\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class PublicationSearchFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $query->whereHas('video', function (Builder $query) use ($value) {
            $query->where('title', 'like', "{$value}%");
        });
    }
}
