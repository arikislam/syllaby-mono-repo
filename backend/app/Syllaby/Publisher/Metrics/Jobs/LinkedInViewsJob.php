<?php

namespace App\Syllaby\Publisher\Metrics\Jobs;

use Override;
use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Syllaby\Publisher\Metrics\PublicationMetricKey;
use App\Syllaby\Publisher\Publications\AccountPublication;
use App\Syllaby\Publisher\Channels\Vendors\Individual\LinkedInProvider;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;

class LinkedInViewsJob extends AbstractHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected AccountPublication $publication)
    {
        //
    }

    public function handle(): void
    {
        $this->publication->load('channel.account');

        try {
            $this->validate()
                ->fetch()
                ->transform()
                ->prepare()
                ->store();
        } catch (InvalidRefreshTokenException) {
            $this->writeLog("Invalid Refresh Token While fetching LinkedIn Views for {$this->publication->channel->name}");

            return;
        } catch (Throwable $e) {
            $this->writeLog('Failed to fetch LinkedIn Views for {id}', [
                'id' => $this->publication->channel->id,
                'reason' => $e->getMessage(),
            ]);

            return;
        }
    }

    protected function fetch(): AbstractHandler
    {
        $this->response = Http::withHeaders([
            'Authorization' => "Bearer {$this->publication->channel->account->access_token}",
            'LinkedIn-Version' => config('services.linkedin.api_version'),
            'X-Restli-Protocol-Version' => config('services.linkedin.protocol_version'),
        ])->get('https://api.linkedin.com/v2/videoAnalytics', [
            'q' => 'entity',
            'entity' => $this->publication->provider_media_id,
            'type' => 'VIDEO_VIEW',
        ])->throw();

        return $this;
    }

    protected function transform(): AbstractHandler
    {
        $this->data = collect($this->response->json('elements'))->map(fn ($item) => [
            'id' => $item['entity'],
            'slug' => 'views-count',
            'value' => $item['value'],
        ]);

        return $this;
    }

    #[Override]
    protected function prepare(): AbstractHandler
    {
        $keys = PublicationMetricKey::select(['id', 'slug'])->get();

        $this->insertions = $this->data->map(fn ($item) => [
            'publication_id' => $this->publication->publication_id,
            'social_channel_id' => $this->publication->social_channel_id,
            'publication_metric_key_id' => $keys->firstWhere('slug', $item['slug'])->id,
            'value' => $item['value'],
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        return $this;
    }

    protected function validate(): AbstractHandler
    {
        if (app(LinkedInProvider::class)->validate($this->publication->channel)) {
            return $this;
        }

        return tap($this, callback: function () {
            $this->publication->channel->account()->associate(app(LinkedInProvider::class)->refresh($this->publication->channel->account));
        });
    }
}
