<?php

namespace App\Syllaby\Tags\Filters;

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use Spatie\QueryBuilder\Filters\Filter;
use App\Syllaby\Assets\Enums\AssetType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

readonly class TagsTypeFilter implements Filter
{
    public function __construct(private User $user) {}

    /**
     * Apply the filter.
     */
    public function __invoke(Builder $query, $value, string $property): void
    {
        match ($property) {
            'templates' => $query->whereHas('templates', function (Builder $query) use ($value) {
                $query->where('type', $value)->where(function (Builder $query) {
                    $query->where('user_id', $this->user->id)->orWhere('user_id', null);
                });
            }),

            'media' => $query->whereHas('media', function (Builder $query) use ($value) {
                $assets = Asset::where(function (Builder $query) {
                    $query->where('user_id', $this->user->id)->orWhere('user_id', null);
                })->where('type', AssetType::AUDIOS->value)->pluck('id')->toArray();

                $query->where('collection_name', $value)
                    ->whereIn('model_id', $assets)
                    ->where('model_type', Relation::getMorphAlias(Asset::class));

                // TODO: When we backfill the media table with the user_id relation, we can use the following line
                // $query->where('collection_name', $value)->where(function (Builder $query) {
                //     $query->where('user_id', $this->user->id)->orWhere('user_id', null);
                // });
            }),
            default => null,
        };
    }
}
