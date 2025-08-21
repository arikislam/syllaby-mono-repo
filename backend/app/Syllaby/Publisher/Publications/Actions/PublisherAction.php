<?php

namespace App\Syllaby\Publisher\Publications\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Publications\Vendors\Publisher;
use App\Syllaby\Publisher\Channels\Exceptions\InvalidRefreshTokenException;
use App\Syllaby\Publisher\Publications\Exceptions\PublicationFailedException;

class PublisherAction
{
    const string LOCK_PREFIX = 'publication-event:';

    /** @throws InvalidRefreshTokenException|PublicationFailedException */
    public function handle(array $input, string $provider, Publication $publication, SocialChannel $channel): ?Publication
    {
        if (Arr::get($input, 'detach', false) === true) {
            $publication->channels()->detach($channel->id);

            return null;
        }

        $type = PostType::from(Arr::get($input, 'post_type', 'post'));
        $publication = Publisher::driver($provider)->prepare($publication, $channel, $input, $type);

        $this->createCalendarEvent($publication, $input);

        if (! Arr::get($input, 'scheduled_at')) {
            return Publisher::driver($provider)->publish($publication, $channel, $type);
        }

        return $publication;
    }

    private function createCalendarEvent(Publication $publication, array $input): void
    {
        $lock = Cache::lock(static::LOCK_PREFIX.$publication->id, 10);

        if ($lock->get()) {
            try {
                $now = now();
                $date = Arr::get($input, 'scheduled_at');

                $publication->event()->updateOrCreate(['user_id' => $publication->user_id], [
                    'updated_at' => $now,
                    'starts_at' => $date ?? $now,
                    'ends_at' => $date ?? $now,
                    'completed_at' => filled($date) ? null : $now,
                    'scheduler_id' => Arr::get($input, 'scheduler_id'),
                ]);
            } finally {
                $lock->release();
            }
        }
    }
}
