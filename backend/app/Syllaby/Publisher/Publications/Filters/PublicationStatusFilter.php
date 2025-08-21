<?php

namespace App\Syllaby\Publisher\Publications\Filters;

use Arr;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class PublicationStatusFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $query->whereHas('channels', function ($query) use ($value) {
            $query->whereIn('account_publications.status', Arr::wrap($value));
        });

        $query->with(['channels' => function ($query) use ($value) {
            $query->whereIn('account_publications.status', Arr::wrap($value));
        }]);
    }
}