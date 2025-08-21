<?php

namespace App\Http\Requests\Clones;

use Illuminate\Validation\Rule;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVoiceCloneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): bool
    {
        return $gate->allows('update', $this->route('clonable'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'terms' => ['required', 'accepted'],
            'name' => ['required', 'string', 'max:125'],
            'description' => ['nullable', 'string', 'max:255'],
            'provider' => ['required', 'string', Rule::in(['elevenlabs'])],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
        ];
    }
}
