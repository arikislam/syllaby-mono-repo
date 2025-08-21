<?php

namespace App\Http\Requests\Publication;

use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use App\Syllaby\Publisher\Publications\Rules\ValidTags;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Rules\ValidSocialChannel;
use App\Syllaby\Publisher\Publications\Rules\NoAngleBrackets;
use App\Syllaby\Publisher\Publications\Rules\ValidPublication;
use App\Syllaby\Publisher\Publications\Enums\YoutubePrivacyStatus;
use App\Syllaby\Publisher\Publications\Rules\EnsureValidWeeksRange;

class YoutubePublicationRequest extends PublicationRequest
{
    public function authorize(Gate $gate): Response
    {
        return $gate->inspect('create', [$this->publication, SocialAccountEnum::Youtube->toString(), $this->input('scheduled_at')]);
    }

    public function rules(): array
    {
        return [
            'publication_id' => ['required', 'integer', new ValidPublication(SocialAccountEnum::Youtube->toString(), $this->publication)],
            'channel_id' => ['required', 'integer', new ValidSocialChannel(SocialAccountEnum::Youtube->toString(), $this->channel)],
            'scheduled_at' => ['sometimes', 'date', 'after_or_equal:now', new EnsureValidWeeksRange],
            'title' => ['sometimes', 'string', 'max:100', new NoAngleBrackets],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000', new NoAngleBrackets],
            'privacy_status' => ['sometimes', Rule::in(YoutubePrivacyStatus::values())],
            'category' => ['sometimes', 'nullable', 'integer'],
            'tags' => ['sometimes', 'nullable', 'array', new ValidTags],
            'tags.*' => ['sometimes', 'string', 'max:450'],
            'license' => ['sometimes', 'string', Rule::in('creativeCommon', 'youtube')],
            'embeddable' => ['sometimes', 'boolean'],
            'notify_subscribers' => ['sometimes', 'boolean'],
            'made_for_kids' => ['sometimes', 'boolean'],
            'detach' => ['sometimes', 'boolean'],
        ];
    }
}
