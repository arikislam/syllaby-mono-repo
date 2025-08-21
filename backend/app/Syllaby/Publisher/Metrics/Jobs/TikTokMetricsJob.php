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
use App\Syllaby\Publisher\Channels\Vendors\Individual\TikTokProvider;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

class TikTokMetricsJob extends AbstractHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const array PARAMS = ['id', 'like_count', 'comment_count', 'share_count', 'view_count'];

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
            $this->writeLog("Invalid Refresh Token While fetching TikTok Metrics for {$this->publications->first()->channel->name}");

            return;
        } catch (Throwable $e) {
            $this->writeLog('Failed to fetch TikTok Metrics for {id}', [
                'id' => $this->publications->first()->channel->id,
                'reason' => $e->getMessage(),
            ]);

            return;
        }
    }

    protected function fetch(): AbstractHandler
    {
        $this->response = Http::withToken($this->publications->first()->channel->account->access_token)
            ->contentType('application/json')
            ->post('https://open.tiktokapis.com/v2/video/query/?fields='.implode(',', self::PARAMS), [
                'filters' => ['video_ids' => $this->publications->pluck('provider_media_id')->toArray()],
            ])->throw();

        return $this;
    }

    protected function transform(): AbstractHandler
    {
        $this->data = collect($this->response->json('data.videos'))->flatMap(fn ($item) => [
            ['id' => $item['id'], 'slug' => 'views-count', 'value' => (int) $item['view_count']],
            ['id' => $item['id'], 'slug' => 'likes-count', 'value' => (int) $item['like_count']],
            ['id' => $item['id'], 'slug' => 'comments-count', 'value' => (int) $item['comment_count']],
            ['id' => $item['id'], 'slug' => 'shares-count', 'value' => (int) $item['share_count']],
        ]);

        return $this;
    }

    protected function validate(): AbstractHandler
    {
        if (app(TikTokProvider::class)->validate($this->publications->first()->channel)) {
            return $this;
        }

        $account = app(TikTokProvider::class)->refresh($this->publications->first()->channel->account);

        return tap($this, callback: function () use ($account) {
            $this->publications->each(function (AccountPublication $publication) use ($account) {
                return $publication->channel->account()->associate($account);
            });
        });
    }
}
