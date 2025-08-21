<?php

namespace App\Syllaby\Publisher\Metrics\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Metrics\Jobs\LinkedInViewsJob;
use App\Syllaby\Publisher\Metrics\Jobs\TikTokMetricsJob;
use App\Syllaby\Publisher\Metrics\Jobs\YoutubeMetricsJob;
use App\Syllaby\Publisher\Publications\AccountPublication;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Metrics\Jobs\LinkedInReactionsJob;

class FetchPublicationMetrics extends Command
{
    const int CHUNK_SIZE = 10;

    protected $signature = 'metrics:fetch-publication-metrics';

    protected $description = 'Fetch publication metrics from social media providers';

    public function handle(): int
    {
        $this->fetchAccountsFor(SocialAccountEnum::Youtube)->chunk(self::CHUNK_SIZE, function ($publications) {
            $publications->groupBy('social_channel_id')->each($this->dispatchJobs(YoutubeMetricsJob::class));
        });

        $this->fetchAccountsFor(SocialAccountEnum::TikTok)->chunk(self::CHUNK_SIZE, function ($publications) {
            $publications->groupBy('social_channel_id')->each($this->dispatchJobs(TikTokMetricsJob::class));
        });

        $this->fetchAccountsFor(SocialAccountEnum::LinkedIn)->chunk(self::CHUNK_SIZE, function ($publications) {
            $publications->groupBy('social_channel_id')->each($this->dispatchJobs(LinkedInReactionsJob::class));
        });

        $this->fetchAccountsFor(SocialAccountEnum::LinkedIn, [SocialChannel::ORGANIZATION])->chunk(self::CHUNK_SIZE, function ($publications) {
            $publications->each(fn ($publication) => LinkedInViewsJob::dispatch($publication));
        });

        return self::SUCCESS;
    }

    private function fetchAccountsFor(SocialAccountEnum $account, array $types = [SocialChannel::ORGANIZATION, SocialChannel::INDIVIDUAL]): Builder
    {
        return AccountPublication::query()
            ->whereNotNull('provider_media_id')
            ->whereDate('created_at', '>=', now()->subMonths(3))
            ->whereHas('channel', function ($query) use ($account, $types) {
                return $query->whereHas('account', function ($query) use ($account) {
                    return $query->where('provider', $account)->where('needs_reauth', false);
                })->whereIn('type', $types);
            });
    }

    private function dispatchJobs($job): callable
    {
        return function ($groups) use ($job) {
            $groups->chunk(5)->each(fn ($publications) => $job::dispatch($publications));
        };
    }
}
