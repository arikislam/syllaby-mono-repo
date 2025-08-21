<?php

namespace App\Syllaby\Publisher\Publications\Actions;

use Arr;
use Closure;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Collection;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Publications\DTOs\TikTokVideoData;
use App\Syllaby\Publisher\Publications\DTOs\ThreadsVideoData;
use App\Syllaby\Publisher\Publications\DTOs\YoutubeVideoData;
use App\Syllaby\Publisher\Publications\DTOs\FacebookVideoData;
use App\Syllaby\Publisher\Publications\DTOs\LinkedInVideoData;
use App\Syllaby\Publisher\Publications\DTOs\InstagramVideoData;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Publications\Contracts\VideoDataContract;

class DraftPublicationAction
{
    public function handle(array $input): Publication
    {
        $publication = Publication::query()->find(Arr::get($input, 'publication_id'));

        $data = collect($input)->except(['publication_id', 'scheduled_at'])
            ->flatMap($this->normalize($this->fetchAccounts($input)))
            ->reduce(fn ($carry, $item) => $carry + $item, []);

        return tap($publication, fn (Publication $publication) => attempt(function () use ($input, $data, $publication) {
            $publication->update(['draft' => true, 'temporary' => false, 'scheduled' => Arr::has($input, 'scheduled_at')]);

            return $publication->channels()->sync($data, false);
        }));
    }

    public function normalize(Collection $socials): Closure
    {
        return function ($channels) use ($socials) {
            return collect($channels)->map(function ($channel) use ($socials) {
                $dto = $this->resolveDto($socials->get(Arr::get($channel, 'channel_id')));

                return [$channel['channel_id'] => $this->attributes($dto, $channel)];
            });
        };
    }

    private function fetchAccounts(array $input): Collection
    {
        $ids = collect($input)
            ->except(['publication_id', 'scheduled_at'])
            ->flatten(1)
            ->pluck('channel_id')
            ->unique()
            ->all();

        return SocialChannel::whereIn('id', $ids)->with('account')->get()->keyBy('id');
    }

    private function attributes(string $dto, array $account): array
    {
        /** @var VideoDataContract $dto */
        $metadata = $dto::fromArray($account)->toArray();

        return [
            'metadata' => $metadata,
            'status' => SocialUploadStatus::DRAFT->value,
            'post_type' => PostType::from(Arr::get($account, 'post_type', 'post'))->value,
        ];
    }

    private function resolveDto(SocialChannel $channel): string
    {
        return match ($channel->account->provider) {
            SocialAccountEnum::Youtube => YoutubeVideoData::class,
            SocialAccountEnum::TikTok => TikTokVideoData::class,
            SocialAccountEnum::LinkedIn => LinkedInVideoData::class,
            SocialAccountEnum::Facebook => FacebookVideoData::class,
            SocialAccountEnum::Instagram => InstagramVideoData::class,
            SocialAccountEnum::Threads => ThreadsVideoData::class,
            default => throw new InvalidArgumentException('Invalid provider')
        };
    }
}
