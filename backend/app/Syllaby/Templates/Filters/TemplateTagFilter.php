<?php

namespace App\Syllaby\Templates\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class TemplateTagFilter implements Filter
{
    /**
     * Apply the filter.
     */
    public function __invoke(Builder $query, $value, string $property): void
    {
        $query->whereHas('tags', fn ($query) => $query->where('slug', $value));
    }
}
