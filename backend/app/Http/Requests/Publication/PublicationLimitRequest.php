<?php

namespace App\Http\Requests\Publication;

use Illuminate\Foundation\Http\FormRequest;

class PublicationLimitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'channels' => ['required', 'array'],
            'channels.*' => ['integer', 'exists:social_channels,id'],
            'date' => ['sometimes', 'date_format:Y-m-d'],
        ];
    }
}
