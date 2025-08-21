<?php

namespace App\Http\Requests\Publication;

use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Rules\ValidSocialChannel;
use App\Syllaby\Publisher\Publications\Rules\ValidPublication;
use App\Syllaby\Publisher\Publications\Rules\EnsureValidWeeksRange;

class LinkedInPublicationRequest extends PublicationRequest
{
    public function authorize(Gate $gate): Response
    {
        return $gate->inspect('create', [$this->publication, SocialAccountEnum::LinkedIn->toString(), $this->input('scheduled_at')]);
    }

    public function rules(): array
    {
        return [
            'publication_id' => ['required', 'integer', new ValidPublication(SocialAccountEnum::LinkedIn->toString(), $this->publication)],
            'channel_id' => ['required', 'integer', new ValidSocialChannel(SocialAccountEnum::LinkedIn->toString(), $this->channel)],
            'scheduled_at' => ['sometimes', 'date', 'after_or_equal:now', new EnsureValidWeeksRange],
            'visibility' => ['sometimes', Rule::in(['CONNECTIONS', 'PUBLIC'])],
            'caption' => ['required', 'string', 'max:2200'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'detach' => ['sometimes', 'boolean'],
        ];
    }
}
