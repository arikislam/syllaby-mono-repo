<?php

namespace App\Http\Requests\Clones;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreateAvatarCloneRequest extends FormRequest
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
            'terms' => ['required', 'accepted'],
            'url' => ['required', 'url', 'active_url'],
            'name' => ['required', 'string', 'max:125'],
            'provider' => ['required', 'string', Rule::in(['fastvideo'])],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
        ];
    }
}
