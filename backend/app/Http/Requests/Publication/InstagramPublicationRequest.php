<?php

namespace App\Http\Requests\Publication;

use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Validation\ValidationException;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Rules\ValidSocialChannel;
use App\Syllaby\Publisher\Publications\Rules\ValidPublication;
use App\Syllaby\Publisher\Publications\Rules\EnsureValidWeeksRange;

class InstagramPublicationRequest extends PublicationRequest
{
    public function authorize(Gate $gate): Response
    {
        return $gate->inspect('create', [$this->publication, SocialAccountEnum::Instagram->toString(), $this->input('scheduled_at')]);
    }

    public function rules(): array
    {
        return [
            'publication_id' => ['required', 'integer', new ValidPublication(SocialAccountEnum::Instagram->toString(), $this->publication, $this->input('post_type', 'reel'))],
            'channel_id' => ['required', 'integer', new ValidSocialChannel(SocialAccountEnum::Instagram->toString(), $this->channel)],
            'scheduled_at' => ['sometimes', 'date', 'after_or_equal:now', new EnsureValidWeeksRange],
            'post_type' => ['required', 'string', Rule::in(PostType::instagram())],
            'caption' => ['sometimes', 'nullable', 'string', 'max:2200'],
            'share_to_feed' => ['sometimes', 'boolean'],
            'detach' => ['sometimes', 'boolean'],
        ];
    }

    public function ensureIsBusinessAccount(): void
    {
        $container = Http::meta()->post("{$this->channel->provider_id}/media", [
            'media_type' => 'STORIES',
            'video_url' => $this->publication->asset()->getUrl(),
            'access_token' => $this->channel->account->access_token,
        ]);

        if ($container->failed() && $container->json('error.code') === 10) {
            throw ValidationException::withMessages(['channel_id' => __('publish.incompatible_account')]);
        }
    }

    public function isStory(): bool
    {
        return $this->input('post_type') === PostType::STORY->value;
    }
}
