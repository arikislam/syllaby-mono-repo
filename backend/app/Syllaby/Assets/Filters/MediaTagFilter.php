<?php

namespace App\Syllaby\Assets\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class MediaTagFilter implements Filter
{
    /**
     * Apply the filter.
     */
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $tags = is_array($value) ? $value : [$value];

        $query->whereHas('tags', fn ($query) => $query->whereIn('slug', $tags));
    }
}
