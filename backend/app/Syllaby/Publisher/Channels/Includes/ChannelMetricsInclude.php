<?php

namespace App\Syllaby\Publisher\Channels\Includes;

use Cache;
use Illuminate\Database\Eloquent\Builder;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Spatie\QueryBuilder\Includes\IncludeInterface;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Metrics\PublicationMetricKey;

class ChannelMetricsInclude implements IncludeInterface
{
    public function __construct(protected Publication $publication)
    {
    }

    public function __invoke(SocialChannel|Builder $query, string $include): void
    {
        $query
            ->metricsFor('views_count', $this->publication, $this->keys()->firstWhere('slug', 'views-count'))
            ->metricsFor('likes_count', $this->publication, $this->keys()->firstWhere('slug', 'likes-count'))
            ->metricsFor('comments_count', $this->publication, $this->keys()->firstWhere('slug', 'comments-count'));
    }

    private function keys()
    {
        return Cache::remember('publication_metric_keys', 60 * 60 * 24, function () {
            return PublicationMetricKey::all();
        });
    }
}