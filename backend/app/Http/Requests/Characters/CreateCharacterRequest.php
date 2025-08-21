<?php

namespace App\Http\Requests\Characters;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Foundation\Http\FormRequest;

class CreateCharacterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', File::image()->max('10mb')],
            'genre_id' => ['nullable', 'integer', Rule::exists('genres', 'id')],
            'name' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:500'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female'])],
            'traits' => ['nullable', 'array'],
            'age' => ['nullable', 'string'],
        ];
    }
}
