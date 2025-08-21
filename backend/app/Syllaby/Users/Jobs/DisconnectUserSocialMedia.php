<?php

namespace App\Syllaby\Users\Jobs;

use App\Syllaby\Users\User;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Channels\Actions\DisconnectAction;

class DisconnectUserSocialMedia implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected User $user) {}

    /**
     * Execute the job.
     */
    public function handle(DisconnectAction $disconnect): void
    {
        SocialChannel::with('account')->whereIn('social_account_id', $this->user->socialAccounts()->pluck('id'))->get()->each(
            fn (SocialChannel $channel) => $disconnect->handle(['id' => $channel->id], $channel->account->provider)
        );

        $this->user->socialAccounts()->delete();
    }
}
