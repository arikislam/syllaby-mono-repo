<?php

namespace App\Http\Requests\Social;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Publisher\Channels\SocialChannel;

class SocialDisconnectRequest extends FormRequest
{
    public function authorize()
    {
        $channel = SocialChannel::where('id', $this->input('id'))->first();

        return $this->user()->can('delete', $channel);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'int', Rule::exists('social_channels', 'id')]
        ];
    }
}
