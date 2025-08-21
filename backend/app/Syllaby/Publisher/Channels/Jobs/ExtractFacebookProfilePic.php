<?php

namespace App\Syllaby\Publisher\Channels\Jobs;

use Log;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Assets\Actions\TransloadMediaAction;

class ExtractFacebookProfilePic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected SocialAccount $account) {}

    public function handle(): void
    {
        $pages = $this->account->channels()->where('type', SocialChannel::PAGE)->get();

        $pages->each(function (SocialChannel $channel) {
            if (! $avatar = $this->extractAvatar($channel)) {
                return;
            }

            $media = app(TransloadMediaAction::class)->handle($channel, $avatar, 'avatar');

            $channel->update(['avatar' => $media->getFullUrl()]);
        });
    }

    private function extractAvatar(SocialChannel $channel)
    {
        $response = Http::meta()->get("{$channel->provider_id}/picture", [
            'redirect' => false,
            'access_token' => $this->account->access_token,
            'type' => 'large',
        ]);

        if ($response->failed()) {
            Log::alert("Failed to extract Facebook profile picture for channel {$channel->id}", [$response->json()]);

            return null;
        }

        return $response->json('data.url');
    }
}
