<?php

namespace App\Syllaby\Videos\Jobs;

use Exception;
use Throwable;
use Illuminate\Support\Arr;
use App\Syllaby\Videos\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Publications\Actions\PublisherAction;
use App\Syllaby\Publisher\Publications\Concerns\BuildsSocialPayload;
use App\Syllaby\Publisher\Publications\Actions\CreatePublicationAction;

class ProcessVideoPublicationsJob implements ShouldBeUnique, ShouldQueue
{
    use BuildsSocialPayload, Queueable;

    public function __construct(protected Video $video) {}

    public function handle(): void
    {
        if (! $this->hasPublicationIntent()) {
            return;
        }

        $channels = Arr::get($this->video->metadata, 'publications');

        $publication = $this->getPublication();

        $input = [
            'post_type' => PostType::POST->value,
            'description' => Arr::get($this->video->metadata, 'custom_description'),
        ];

        if (Arr::get($this->video->metadata, 'ai_labels', false) === true) {
            $input['ai_tags'] = $this->generateTags($this->video->faceless->script);
            $input['ai_description'] = $this->generateDescription($this->video->faceless->script);
        }

        $remaining = [];

        foreach ($channels as $intent) {
            try {
                $this->processPublication($publication, $intent, $input);
            } catch (Throwable) {
                $remaining[] = $intent;
            }
        }

        if (filled($remaining)) {
            $this->video->update(['metadata->publications' => $remaining]);
            throw new Exception;
        }

        $metadata = Arr::except($this->video->metadata, 'publication_id');

        Arr::set($metadata, 'publications', null);

        $this->video->update(['metadata' => $metadata]);
    }

    public function uniqueId(): string
    {
        return $this->video->id;
    }

    protected function processPublication(Publication $publication, array $intent, array $input): void
    {
        $channel = SocialChannel::where('id', Arr::get($intent, 'channel_id'))
            ->whereRelation('account', 'user_id', $this->video->user->id)
            ->first();

        if (! $channel) {
            Log::error("Channel not found for publication intent: {$intent['channel_id']}");

            return;
        }

        $input = array_merge($input, [
            'scheduled_at' => Arr::get($intent, 'scheduled_at'),
        ]);

        $provider = $channel->account->provider->toString();
        $payload = array_merge(
            $input, $this->buildPayload($provider, $this->video->title, $input)
        );

        app(PublisherAction::class)->handle($payload, $provider, $publication, $channel);
    }

    protected function hasPublicationIntent(): bool
    {
        $publications = Arr::get($this->video->metadata, 'publications');

        return filled($publications) && is_array($publications);
    }

    private function getPublication(): Publication
    {
        if ($id = Arr::get($this->video->metadata, 'publication_id')) {
            return Publication::find($id);
        }

        $publication = app(CreatePublicationAction::class)->handle(['video_id' => $this->video->id], $this->video->user);

        return tap($publication, fn () => $this->video->update(['metadata->publication_id' => $publication->id]));
    }
}
