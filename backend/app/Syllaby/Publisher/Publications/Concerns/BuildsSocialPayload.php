<?php

namespace App\Syllaby\Publisher\Publications\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Generators\Vendors\Assistants\Chat;
use App\Syllaby\Publisher\Metadata\Prompts\TagPrompt;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Publications\DTOs\TikTokVideoData;
use App\Syllaby\Publisher\Metadata\Prompts\DescriptionPrompt;
use App\Syllaby\Publisher\Publications\DTOs\ThreadsVideoData;
use App\Syllaby\Publisher\Publications\DTOs\YoutubeVideoData;
use App\Syllaby\Publisher\Publications\DTOs\FacebookVideoData;
use App\Syllaby\Publisher\Publications\DTOs\LinkedInVideoData;
use App\Syllaby\Publisher\Publications\DTOs\InstagramVideoData;
use App\Syllaby\Publisher\Publications\Enums\TikTokPrivacyStatus;
use App\Syllaby\Publisher\Publications\Enums\YoutubePrivacyStatus;

trait BuildsSocialPayload
{
    /**
     * Build the payload for the given provider.
     */
    protected function buildPayload(string $provider, string $title, array $input): array
    {
        $method = Str::camel("{$provider}Payload");

        return $this->{$method}($title, $input);
    }

    /**
     * Generate the tags for the publication.
     */
    protected function generateTags(string $script): array
    {
        $response = Chat::driver('gpt')->send(TagPrompt::generate($script));

        return collect(explode(',', $response->text))
            ->map(fn ($tag) => '#'.Str::of($tag)->trim()->replace([' ', '-'], '')->lower()->value())
            ->take(4)
            ->all();
    }

    /**
     * Generate the description for the publication.
     */
    protected function generateDescription(string $script): string
    {
        $response = Chat::driver('gpt')->send(DescriptionPrompt::generate($script));

        return Str::limitNaturally($response->text);
    }

    /**
     * Build the payload for Facebook.
     */
    protected function facebookPayload(string $title, array $input): array
    {
        return (new FacebookVideoData(
            caption: $this->caption($input, $title, 5000),
            title: null,
            video_id: null
        ))->toArray();
    }

    /**
     * Build the payload for YouTube.
     */
    protected function youtubePayload(string $title, array $input): array
    {
        $description = sprintf('%s %s',
            Arr::get($input, 'ai_description', ''),
            Arr::get($input, 'description', ''),
        );

        return (new YoutubeVideoData(
            title: $title,
            description: Str::limitNaturally($description, 5000),
            privacy_status: YoutubePrivacyStatus::PUBLIC->value,
            category: null,
            tags: Arr::get($input, 'ai_tags', null),
            license: 'youtube',
            embeddable: true,
            made_for_kids: false,
            notify_subscribers: true,
        ))->toArray();
    }

    /**
     * Build the payload for TikTok.
     */
    protected function tiktokPayload(string $title, array $input): array
    {
        return (new TikTokVideoData(
            caption: $this->caption($input, $title, 2200),
            allow_comments: true,
            allow_duet: true,
            allow_stitch: true,
            privacy_status: TikTokPrivacyStatus::PUBLIC_TO_EVERYONE->name,
            publish_id: null,
        ))->toArray();
    }

    /**
     * Build the payload for LinkedIn.
     */
    protected function linkedinPayload(string $title, array $input): array
    {
        return (new LinkedInVideoData(
            caption: $this->caption($input, $title, 2200),
            title: null,
            visibility: 'PUBLIC',
        ))->toArray();
    }

    /**
     * Build the payload for Instagram.
     */
    protected function instagramPayload(string $title, array $input): array
    {
        $data = new InstagramVideoData(
            caption: $this->caption($input, $title, 2200),
            video_id: null,
            share_to_feed: true,
        );

        return array_merge($data->toArray(), ['post_type' => PostType::REEL->value]);
    }

    /**
     * Build the payload for Threads.
     */
    protected function threadsPayload(string $title, array $input): array
    {
        return (new ThreadsVideoData(
            caption: $this->caption($input, $title),
        ))->toArray();
    }

    /**
     * Build the caption for the publication.
     */
    protected function caption(array $input, string $title, int $limit = 500): string
    {
        $tags = implode(' ', Arr::get($input, 'ai_tags', []));

        $caption = sprintf('%s %s %s',
            Arr::get($input, 'description', $title),
            Arr::get($input, 'ai_description', ''),
            $tags ? " {$tags}" : '',
        );

        return Str::limitNaturally($caption, $limit);
    }
}
