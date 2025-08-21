<?php

namespace App\Http\Requests\Ideas;

use Illuminate\Foundation\Http\FormRequest;

class RelatedTopicRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string'],
            'language' => ['nullable', 'string'],
        ];
    }
}
