<?php

namespace App\Shared\Facebook;

use FacebookAds\Api;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use FacebookAds\Object\ServerSide\Event;
use GuzzleHttp\Promise\PromiseInterface;
use FacebookAds\Object\ServerSide\UserData;
use Illuminate\Contracts\Support\Arrayable;
use FacebookAds\Object\ServerSide\EventRequestAsync;

class FacebookPixel
{
    protected string $pixel;

    protected Collection $events;

    protected UserData $userData;

    public function __construct()
    {
        $this->events = collect();
        $this->pixel = config('services.facebook.pixel.id');
        Api::init(null, null, config('services.facebook.pixel.access_token'), false);
    }

    public function track(string $id, string $event, array $data): void
    {
        $user = Arr::get($data, 'user');
        $cookies = Arr::pluck(Arr::get($data, 'cookies.facebook'), 'value', 'key');

        $this->setUserData(PixelUserData::create($user, $cookies));

        $this->addEvent(CustomEvent::create($id, $event, $this->getUserData(), [
            'url' => Arr::get($data, 'url'),
            'custom' => Arr::get($data, 'custom'),
            'source' => Arr::get($data, 'source'),
        ]))->sendEvents();
    }

    public function setUserData(UserData $userData): self
    {
        $this->userData = $userData;

        return $this;
    }

    public function getUserData(): UserData
    {
        return $this->userData;
    }

    public function addEvent(Event $event): self
    {
        $this->events->push($event);

        return $this;
    }

    public function addEvents(Arrayable $events): self
    {
        $this->events = $this->events->merge($events);

        return $this;
    }

    public function setEvents(iterable $events): self
    {
        $this->events = collect($events);

        return $this;
    }

    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function clearEvents(): self
    {
        return $this->setEvents([]);
    }

    public function sendEvents(): PromiseInterface
    {
        $http = new EventRequestAsync($this->pixel);
        $http->setEvents($this->events->toArray());

        if ($testCode = config('services.facebook.pixel.test_code')) {
            $http->setTestEventCode($testCode);
        }

        return $http->execute();
    }
}
