<?php

namespace App\Syllaby\Publisher\Channels\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Vendors\Individual\TikTokProvider;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

class RefreshTiktokAccessTokens extends Command
{
    protected $signature = 'tiktok:refresh-access-tokens';

    protected $description = 'Refresh TikTok access tokens for all users';

    public function handle(TikTokProvider $tiktok): int
    {
        $this->info('Refreshing TikTok access tokens...');

        $accounts = SocialAccount::where('provider', SocialAccountEnum::TikTok)
            ->where('needs_reauth', false)
            ->cursor();

        $accounts->each(function (SocialAccount $account) use ($tiktok) {
            try {
                $tiktok->refresh($account);
            } catch (InvalidRefreshTokenException) {
                Log::error("Failed to refresh TikTok access token for user {$account->user->name}");
            }
        });

        return self::SUCCESS;
    }
}