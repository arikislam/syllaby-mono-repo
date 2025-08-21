<?php

namespace App\Shared\Facebook;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\UserData;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\ActionSource;

class CustomEvent extends Event
{
    public static function create(string $id, string $name, UserData $user, array $data = []): static
    {
        $event = (new static)
            ->setEventId($id)
            ->setEventName($name)
            ->setUserData($user)
            ->setEventTime(now()->timestamp)
            ->setEventSourceUrl(Arr::get($data, 'url'))
            ->setActionSource(Arr::get($data, 'source', ActionSource::WEBSITE));

        $custom = Arr::get($data, 'custom', []);

        if (! empty($custom)) {
            $custom['value'] = round(Arr::get($custom, 'amount', 0), 2);
            $custom['currency'] = Str::upper(Arr::get($custom, 'currency', 'USD'));

            $event->setCustomData(new CustomData($custom));
        }

        return $event;
    }
}
