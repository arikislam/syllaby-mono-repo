<?php

namespace App\Syllaby\Publisher\Metrics\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Syllaby\Publisher\Metrics\PublicationAggregate;

trait HasMetricAggregates
{
    /**
     * Get all aggregated metrics for this model.
     */
    public function aggregatedMetrics(): HasMany
    {
        return $this->hasMany(PublicationAggregate::class);
    }

    /**
     * Get the views aggregate for this model.
     */
    public function views(): HasMany
    {
        return $this->hasMany(PublicationAggregate::class)->where('key', 'views-count');
    }

    /**
     * Get the likes aggregate for this model.
     */
    public function likes(): HasMany
    {
        return $this->hasMany(PublicationAggregate::class)->where('key', 'likes-count');
    }

    /**
     * Get the comments aggregate for this model.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PublicationAggregate::class)->where('key', 'comments-count');
    }

    /**
     * Get the dislikes aggregate for this model.
     */
    public function dislikes(): HasMany
    {
        return $this->hasMany(PublicationAggregate::class)->where('key', 'dislikes-count');
    }

    /**
     * Get the shares aggregate for this model.
     */
    public function shares(): HasMany
    {
        return $this->hasMany(PublicationAggregate::class)->where('key', 'shares-count');
    }

    /**
     * Get the favorites aggregate for this model.
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(PublicationAggregate::class)->where('key', 'favorites-count');
    }
}
