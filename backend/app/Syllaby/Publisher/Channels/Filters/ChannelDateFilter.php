<?php

namespace App\Syllaby\Publisher\Channels\Filters;

use Carbon\Carbon;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use App\Syllaby\Publisher\Publications\Publication;

class ChannelDateFilter implements Filter
{
    public function __construct(protected Publication $publication)
    {
    }

    public function __invoke(Builder $query, $value, string $property): void
    {
        $query->with([
            'metrics' => function ($query) use ($value) {
                $query->where('publication_id', $this->publication->id)->whereBetween('created_at', [
                    Carbon::parse($value[0])->startOfDay(),
                    Carbon::parse($value[1])->endOfDay(),
                ]);
            },
        ]);
    }
}
