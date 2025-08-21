<?php

namespace App\Syllaby\Publisher\Metrics\Jobs;

use Exception;
use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Syllaby\Publisher\Publications\AccountPublication;
use App\Syllaby\Publisher\Metrics\Services\YoutubeMetricsService;
use App\Syllaby\Publisher\Channels\Vendors\Individual\YoutubeProvider;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

class YoutubeMetricsJob extends AbstractHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param Collection<AccountPublication> $publications */
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
            $this->writeLog("Invalid Refresh Token While fetching Youtube Metrics for {$this->publications->first()->channel->name}");

            return;
        } catch (Throwable $e) {
            $this->writeLog('Failed to fetch Youtube Metrics for {id}', [
                'id' => $this->publications->first()->channel->id,
                'reason' => $e->getMessage(),
            ]);

            return;
        }
    }

    protected function fetch(): AbstractHandler
    {
        if (! $metrics = app(YoutubeMetricsService::class)->fetch($this->publications)) {
            throw new Exception;
        }

        $this->response = $metrics;

        return $this;
    }

    protected function transform(): AbstractHandler
    {
        $this->data = collect($this->response->toSimpleObject()->items)->flatMap(fn ($item) => [
            ['id' => $item->id, 'slug' => 'views-count', 'value' => $item->statistics->viewCount],
            ['id' => $item->id, 'slug' => 'likes-count', 'value' => $item->statistics->likeCount],
            ['id' => $item->id, 'slug' => 'comments-count', 'value' => $item->statistics->commentCount],
            ['id' => $item->id, 'slug' => 'dislikes-count', 'value' => $item->statistics->dislikeCount],
            ['id' => $item->id, 'slug' => 'favorites-count', 'value' => $item->statistics->favoriteCount],
        ]);

        return $this;
    }

    protected function validate(): AbstractHandler
    {
        if (app(YoutubeProvider::class)->validate($this->publications->first()->channel)) {
            return $this;
        }

        $account = app(YoutubeProvider::class)->refresh($this->publications->first()->channel->account);

        return tap($this, callback: function () use ($account) {
            $this->publications->each(function (AccountPublication $publication) use ($account) {
                return $publication->channel->account()->associate($account);
            });
        });
    }
}
