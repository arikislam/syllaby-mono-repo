<?php

namespace App\Syllaby\Videos\Filters;

use App\Syllaby\Videos\Video;
use Spatie\QueryBuilder\Filters\Filter;
use App\Syllaby\Videos\Enums\VideoStatus;
use Illuminate\Database\Eloquent\Builder;

class StatusFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $statuses = is_array($value) ? $value : [$value];

        $query->where(function ($query) use ($statuses) {
            $query->where(function ($query) use ($statuses) {
                $query->where('type', '!=', Video::FACELESS)->whereIn('status', $statuses);
            })->orWhere(function ($query) use ($statuses) {
                $query->where('type', Video::FACELESS)->whereIn('status', $this->except($statuses));
            });
        });
    }

    private function except(mixed $value): array
    {
        return array_values(array_filter($value, fn ($status) => $status !== VideoStatus::DRAFT->value));
    }
}
