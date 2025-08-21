<?php

namespace App\Syllaby\Publisher\Publications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\PublicationLog;

class LogPublicationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Publication $publication,
        protected SocialChannel $channel,
        protected mixed $payload = null
    ) {}

    public function handle(): void
    {
        PublicationLog::updateOrCreate(
            $this->lookupFields(),
            $this->values()
        );
    }

    private function lookupFields(): array
    {
        return [
            'publication_id' => $this->publication->id,
            'social_channel_id' => $this->channel->id,
        ];
    }

    private function values(): array
    {
        return [
            'provider' => $this->channel->account->provider->name,
            'status' => $this->publication->channels()->wherePivot('social_channel_id', $this->channel->id)->first()->pivot->status,
            'payload' => $this->payload,
        ];
    }
}
