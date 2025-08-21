<?php

namespace App\Syllaby\Publisher\Channels\Services\Youtube;

use Google\Client;
use Google\Service\YouTube;
use Google\Service\YouTube\ChannelListResponse;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Assets\Actions\TransloadMediaAction;

class ChannelService
{
    /**
     * The Google API client.
     */
    protected Client $client;

    /**
     * The YouTube service exposed by the Google API client.
     */
    protected YouTube $youtube;

    public function exists(string $token): bool
    {
        $response = $this->init($token)->getChannelsList();

        return $response->getItems() && count($response->getItems()) > 0;
    }

    public function updateNameAndAvatar(string $token): void
    {
        if (! $metadata = $this->init($token)->getChannelsList()->getItems()) {
            return;
        }

        $channel = SocialChannel::where('provider_id', $metadata[0]->getId())->first();

        $avatar = $metadata[0]?->getSnippet()?->getThumbnails()?->getDefault()?->getUrl();

        attempt(function () use ($avatar, $metadata, $channel) {
            $channel->update(['name' => $metadata[0]?->getSnippet()?->getTitle()]);

            if (is_null($avatar)) {
                return;
            }

            $media = app(TransloadMediaAction::class)->handle($channel, $avatar, 'avatar');
            $channel->update(['avatar' => $media->getFullUrl()]);
        });
    }

    public function getChannelsList(): ChannelListResponse
    {
        return $this->youtube->channels->listChannels('snippet,status', ['mine' => true]);
    }

    private function init(string $token): self
    {
        $this->client = new Client;
        $this->client->setDeveloperKey(config('services.youtube.developer_key'));
        $this->client->setAccessToken($token);
        $this->youtube = new YouTube($this->client);

        return $this;
    }
}
