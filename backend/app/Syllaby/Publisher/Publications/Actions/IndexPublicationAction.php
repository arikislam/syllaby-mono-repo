<?php

namespace App\Syllaby\Publisher\Publications\Actions;

use App\Syllaby\Users\User;
use App\Http\Responses\ApiResponse;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Sorts\MetricsSort;
use App\Syllaby\Publisher\Publications\Filters\PublicationSearchFilter;
use App\Syllaby\Publisher\Publications\Filters\PublicationStatusFilter;
use App\Syllaby\Publisher\Publications\Filters\PublicationChannelFilter;
use App\Syllaby\Publisher\Publications\Includes\PublicationMetricsInclude;

class IndexPublicationAction
{
    use ApiResponse;

    public function handle(User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(Publication::class)
            ->whereBelongsTo($user, 'user')
            ->where('temporary', false)
            ->with(['channels.account', 'video.media', 'event', 'video.footage.clones', 'video.faceless'])
            ->select('publications.*')
            ->allowedIncludes(['media', AllowedInclude::custom('aggregate', new PublicationMetricsInclude)])
            ->allowedSorts([
                'created_at',
                AllowedSort::custom('views', new MetricsSort),
                AllowedSort::custom('likes', new MetricsSort),
                AllowedSort::custom('comments', new MetricsSort),
            ])
            ->allowedFilters([
                AllowedFilter::custom('status', new PublicationStatusFilter),
                AllowedFilter::custom('channel', new PublicationChannelFilter),
                AllowedFilter::custom('search', new PublicationSearchFilter),
            ])
            ->latest('publications.created_at')
            ->paginate($this->take());
    }
}
