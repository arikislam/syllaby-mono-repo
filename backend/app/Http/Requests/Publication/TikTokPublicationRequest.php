<?php

namespace App\Http\Requests\Publication;

use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Rules\ValidSocialChannel;
use App\Syllaby\Publisher\Publications\Rules\ValidPublication;
use App\Syllaby\Publisher\Publications\Enums\TikTokPrivacyStatus;
use App\Syllaby\Publisher\Publications\Rules\EnsureValidWeeksRange;

class TikTokPublicationRequest extends PublicationRequest
{
    public function authorize(Gate $gate): Response
    {
        return $gate->inspect('create', [$this->publication, SocialAccountEnum::TikTok->toString(), $this->input('scheduled_at')]);
    }

    public function rules(): array
    {
        return [
            'publication_id' => ['required', 'integer', new ValidPublication(SocialAccountEnum::TikTok->toString(), $this->publication)],
            'channel_id' => ['required', 'integer', new ValidSocialChannel(SocialAccountEnum::TikTok->toString(), $this->channel)],
            'scheduled_at' => ['sometimes', 'date', 'after_or_equal:now', new EnsureValidWeeksRange],
            'caption' => ['sometimes', 'nullable', 'string', 'max:2200'],
            'allow_comments' => ['sometimes', 'boolean'],
            'allow_duet' => ['sometimes', 'boolean'],
            'allow_stitch' => ['sometimes', 'boolean'],
            'privacy_status' => ['sometimes', Rule::in(TikTokPrivacyStatus::values())],
            'detach' => ['sometimes', 'boolean'],
        ];
    }
}
