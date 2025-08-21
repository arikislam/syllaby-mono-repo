<?php

namespace App\Http\Requests\Social;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Publisher\Channels\SocialChannel;

class SocialRefreshRequest extends FormRequest
{
    public function authorize(): bool
    {
        $channel = SocialChannel::where('id', $this->input('id'))->first();

        return $this->user()->can('update', $channel);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'int', Rule::exists('social_channels', 'id')]
        ];
    }
}
