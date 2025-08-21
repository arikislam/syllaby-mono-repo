<?php

namespace App\Http\Controllers\Api\v1\Publication;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Http\Requests\Publication\PublicationLimitRequest;

class PublicationLimitController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Get publication limits and usage for specified channels
     */
    public function index(PublicationLimitRequest $request): JsonResponse
    {
        $date = $request->validated('date') ?? Carbon::now()->format('Y-m-d');

        $channels = SocialChannel::whereIn('id', $request->validated('channels'))->with('account:id,provider')->get();

        $result = $channels->map(function (SocialChannel $channel) use ($date) {
            $platform = $channel->account->provider->toString();
            $limit = config("publications.limits.{$platform}", PHP_INT_MAX);
            $key = $this->getCacheKey($this->user()->id, $channel->id, $platform, $date);

            return [
                'channel_id' => $channel->id,
                'channel_name' => $channel->name,
                'platform' => $platform,
                'limit' => $limit,
                'used' => $used = (int) Redis::get($key) ?: 0,
                'remaining' => $limit - $used,
                'date' => $date,
            ];
        });

        return $this->respondWithArray($result->all());
    }

    /**
     * Get the cache key for tracking publication limits
     */
    protected function getCacheKey(int $user, int $channel, string $platform, string $date): string
    {
        $format = config('publications.cache_format');

        return str_replace(
            ['{user}', '{channel}', '{platform}', '{date}'],
            [$user, $channel, $platform, $date],
            $format
        );
    }
}
