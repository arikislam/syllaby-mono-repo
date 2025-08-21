<?php

namespace App\Http\Requests\Videos;

use Illuminate\Foundation\Http\FormRequest;

class CreateFootageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'idea_id' => ['sometimes', 'integer', 'exists:ideas,id'],
            'campaign_id' => ['sometimes', 'integer', 'exists:campaigns,id'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['required_with:starts_at', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
