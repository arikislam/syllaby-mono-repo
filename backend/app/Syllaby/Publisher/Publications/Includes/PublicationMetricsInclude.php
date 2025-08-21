<?php

namespace App\Syllaby\Publisher\Publications\Includes;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Includes\IncludeInterface;

class PublicationMetricsInclude implements IncludeInterface
{
    public function __invoke(Builder $query, string $include): void
    {
        $query
            ->withSum('views as views_count', 'value')
            ->withSum('likes as likes_count', 'value')
            ->withSum('comments as comments_count', 'value');
    }
}
