<?php

namespace App\Syllaby\Publisher\Metrics\Jobs;

use Http;
use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Syllaby\Publisher\Publications\AccountPublication;
use App\Syllaby\Publisher\Channels\Vendors\Individual\LinkedInProvider;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

class LinkedInReactionsJob extends AbstractHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param  Collection<AccountPublication>  $publications */
    public function __construct(protected Collection $publications) {}

    public function handle(): void
    {
        if ($this->publications->isEmpty()) {
            return;
        }

        $this->publications->each(function (AccountPublication $publication) {
            return $publication->load('channel.account');
        });

        try {
            $this->validate()
                ->fetch()
                ->transform()
                ->prepare()
                ->store();
        } catch (InvalidRefreshTokenException) {
            $this->writeLog("Invalid Refresh Token While fetching LinkedIn Reactions for {$this->publications->first()->channel->name}");

            return;
        } catch (Throwable $e) {
            $this->writeLog('Failed to fetch LinkedIn Reactions for {id}', [
                'id' => $this->publications->first()->channel->id,
                'reason' => $e->getMessage(),
            ]);

            return;
        }
    }

    protected function fetch(): AbstractHandler
    {
        $this->response = Http::withHeaders([
            'LinkedIn-Version' => config('services.linkedin.api_version'),
            'Authorization' => "Bearer {$this->publications->first()->channel->account->access_token}",
        ])->get('https://api.linkedin.com/v2/socialActions', [
            'ids' => $this->publications->pluck('provider_media_id')->toArray(),
        ])->throw();

        return $this;
    }

    protected function transform(): AbstractHandler
    {
        $this->data = collect($this->response->json('results'))->flatMap(fn ($item) => [
            ['id' => $item['target'], 'slug' => 'likes-count', 'value' => $item['likesSummary']['totalLikes']],
            ['id' => $item['target'], 'slug' => 'comments-count', 'value' => $item['commentsSummary']['aggregatedTotalComments']],
        ]);

        return $this;
    }

    protected function validate(): AbstractHandler
    {
        if (app(LinkedInProvider::class)->validate($this->publications->first()->channel)) {
            return $this;
        }

        $account = app(LinkedInProvider::class)->refresh($this->publications->first()->channel->account);

        return tap($this, callback: function () use ($account) {
            $this->publications->each(function (AccountPublication $publication) use ($account) {
                return $publication->channel->account()->associate($account);
            });
        });
    }
}
