<?php

namespace App\Syllaby\Publisher\Publications\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

trait TrackPublications
{
    /**
     * Increment the social publication counter after successful publication
     */
    protected function trackPublication(Request $request): void
    {
        $key = $request->attributes->get('publications_key');

        $ttl = $request->attributes->get('publications_expiry');

        if ($key && $ttl) {
            Redis::incr($key);
            Redis::expire($key, $ttl);
        }
    }
}
