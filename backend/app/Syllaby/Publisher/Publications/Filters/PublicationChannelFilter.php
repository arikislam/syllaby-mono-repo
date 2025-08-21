<?php

namespace App\Syllaby\Publisher\Publications\Filters;

use Arr;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Exceptions\InvalidFilterValue;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

class PublicationChannelFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $invalid = array_diff(Arr::wrap($value), SocialAccountEnum::channels());

        if (count($invalid) > 0) {
            throw new InvalidFilterValue("Invalid filter value for `{$property}`: ".implode(', ', $invalid));
        }

        $providers = array_map(fn (string $value) => SocialAccountEnum::fromString($value)->value, Arr::wrap($value));

        $query->whereHas('channels.account', function ($query) use ($providers) {
            $query->whereIn('social_accounts.provider', $providers);
        });
    }
}
