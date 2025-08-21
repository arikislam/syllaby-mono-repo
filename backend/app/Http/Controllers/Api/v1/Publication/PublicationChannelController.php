<?php

namespace App\Http\Controllers\Api\v1\Publication;

use App\Http\Controllers\Controller;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use App\Http\Resources\SocialChannelResource;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Channels\Filters\ChannelDateFilter;
use App\Http\Requests\Publication\ShowPublicationChannelRequest;
use App\Syllaby\Publisher\Channels\Includes\ChannelMetricsInclude;

class PublicationChannelController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function show(ShowPublicationChannelRequest $request, Publication $publication, SocialChannel $channel)
    {
        $channel = QueryBuilder::for(SocialChannel::class)
            ->allowedIncludes([AllowedInclude::custom('metrics', new ChannelMetricsInclude($publication))])
            ->allowedFilters([
                AllowedFilter::custom('date', new ChannelDateFilter($publication))->default([
                    now()->subDays(7)->startOfDay(), now()->endOfDay(),
                ]),
            ])
            ->where('social_channels.id', $channel->id)
            ->first();

        return $this->respondWithResource(SocialChannelResource::make($channel));
    }
}
