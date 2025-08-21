<?php

namespace App\Http\Requests\Social;

use Illuminate\Foundation\Http\FormRequest;

class StoreChannelRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'provider_id' => ['required', 'string'],
            'channels' => ['required', 'array'],
            'channels.*.id' => ['required', 'string'],
            'channels.*.type' => ['required', 'string'],
            'channels.*.name' => ['required', 'string'],
        ];
    }
}
