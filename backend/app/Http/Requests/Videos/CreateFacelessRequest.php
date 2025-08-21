<?php

namespace App\Http\Requests\Videos;

use Illuminate\Validation\Rule;
use App\Syllaby\Videos\Enums\FacelessType;
use Illuminate\Foundation\Http\FormRequest;

class CreateFacelessRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', Rule::in(FacelessType::values())],
            'idea_id' => ['sometimes', 'integer', 'exists:ideas,id'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
