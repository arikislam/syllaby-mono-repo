<?php

namespace App\Shared\Facebook;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use FacebookAds\Object\ServerSide\UserData;

class PixelUserData extends UserData
{
    /**
     * Setup default data to identify a user within Facebook Conversion API.
     */
    public static function create(array $user, array $cookies): self
    {
        return (new self())
            ->setFbp(Arr::get($cookies, '_fbp'))
            ->setFbc(Arr::get($cookies, '_fbc'))
            ->setClientIpAddress(request()->ip())
            ->setExternalId(Arr::get($user, 'id'))
            ->setClientUserAgent(request()->userAgent())
            ->setFirstName(auth()->user()?->name)
            ->setEmail(Str::lower(Arr::get($user, 'email')));
    }
}
