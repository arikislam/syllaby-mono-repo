<?php

namespace App\Http\Requests\Publication;

use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Rules\ValidSocialChannel;
use App\Syllaby\Publisher\Publications\Rules\ValidPublication;
use App\Syllaby\Publisher\Publications\Rules\EnsureValidWeeksRange;

class FacebookPublicationRequest extends PublicationRequest
{
    public function authorize(Gate $gate): Response
    {
        return $gate->inspect('create', [$this->publication, SocialAccountEnum::Facebook->toString(), $this->input('scheduled_at')]);
    }

    public function rules(): array
    {
        return [
            'publication_id' => ['required', 'integer', new ValidPublication(SocialAccountEnum::Facebook->toString(), $this->publication, $this->input('post_type'))],
            'channel_id' => ['required', 'integer', new ValidSocialChannel(SocialAccountEnum::Facebook->toString(), $this->channel)],
            'scheduled_at' => ['sometimes', 'date', 'after_or_equal:now', new EnsureValidWeeksRange],
            'post_type' => ['required', 'string', Rule::in(PostType::facebook())],
            'caption' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'detach' => ['sometimes', 'boolean'],
        ];
    }
}
