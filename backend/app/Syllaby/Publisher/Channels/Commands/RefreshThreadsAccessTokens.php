<?php

namespace App\Syllaby\Publisher\Channels\Commands;

use Log;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Vendors\Individual\ThreadsProvider;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

class RefreshThreadsAccessTokens extends Command
{
    protected $signature = 'threads:refresh-access-tokens';

    protected $description = 'Refresh Threads access tokens for all users';

    public function handle(ThreadsProvider $provider): void
    {
        $this->info('Refreshing Threads access tokens...');

        $accounts = SocialAccount::where('provider', SocialAccountEnum::Threads)
            ->where('needs_reauth', false)
            ->cursor();

        $accounts->each(function (SocialAccount $account) use ($provider) {
            $expiry = CarbonImmutable::parse($account->updated_at->timestamp + $account->expires_in);

            if (abs($expiry->diffInDays(CarbonImmutable::now())) < 2) {
                try {
                    $provider->refresh($account);
                } catch (InvalidRefreshTokenException) {
                    Log::alert("Failed to refresh Threads access token User [{$account->user->id}] - Channel [{$account->id}]");
                }
            }
        });
    }
}
