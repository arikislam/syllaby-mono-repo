<?php

namespace App\Http\Requests\Metadata;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreatorInfoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', Rule::exists('social_channels', 'id')]
        ];
    }
}
