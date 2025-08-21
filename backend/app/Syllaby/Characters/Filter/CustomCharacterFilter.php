<?php

namespace App\Syllaby\Characters\Filter;

use App\Syllaby\Users\User;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class CustomCharacterFilter implements Filter
{
    public function __construct(protected User $user) {}

    public function __invoke(Builder $query, $value, string $property): void
    {
        match ($value) {
            'all' => $query->ownedBy($this->user),
            'owned' => $query->where('user_id', $this->user->id),
            'system' => $query->whereNull('user_id')
        };
    }
}
