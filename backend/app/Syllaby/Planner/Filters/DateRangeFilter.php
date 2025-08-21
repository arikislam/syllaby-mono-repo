<?php

namespace App\Syllaby\Planner\Filters;

use Carbon\Carbon;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class DateRangeFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property)
    {
        $start = Carbon::parse($value[0])->format('Y-m-d 00:00:00');
        $end = Carbon::parse($value[1])->format('Y-m-d 23:59:59');

        return $query->whereBetween($property, [$start, $end]);
    }
}
