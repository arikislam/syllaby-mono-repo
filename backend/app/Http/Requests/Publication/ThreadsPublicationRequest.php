<?php

namespace App\Http\Requests\Publication;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Rules\ValidSocialChannel;
use App\Syllaby\Publisher\Publications\Rules\ValidPublication;
use App\Syllaby\Publisher\Publications\Rules\EnsureValidWeeksRange;

class ThreadsPublicationRequest extends PublicationRequest
{
    public function authorize(Gate $gate): Response
    {
        return $gate->inspect('create', [$this->publication, SocialAccountEnum::Threads->toString(), $this->input('scheduled_at')]);
    }

    public function rules(): array
    {
        return [
            'publication_id' => ['required', 'integer', new ValidPublication(SocialAccountEnum::Threads->toString(), $this->publication)],
            'channel_id' => ['required', 'integer', new ValidSocialChannel(SocialAccountEnum::Threads->toString(), $this->channel)],
            'scheduled_at' => ['sometimes', 'date', 'after_or_equal:now', new EnsureValidWeeksRange],
            'caption' => ['sometimes', 'nullable', 'string', 'max:500'],
            'detach' => ['sometimes', 'boolean'],
        ];
    }
}
