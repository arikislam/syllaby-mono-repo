<?php

namespace App\Syllaby\Publisher\Channels\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Assets\Actions\TransloadMediaAction;

class ExtractInstagramProfilePic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected SocialAccount $account, protected array $avatars) {}

    public function handle(): void
    {
        $channels = $this->account->channels()->where('type', SocialChannel::PROFESSIONAL_ACCOUNT)->get();

        $channels->each(function (SocialChannel $channel) {
            if (! $avatar = $this->avatars[$channel->provider_id] ?? null) {
                return;
            }

            $media = app(TransloadMediaAction::class)->handle($channel, $avatar, 'avatar');

            $channel->update(['avatar' => $media->getFullUrl()]);
        });
    }
}
