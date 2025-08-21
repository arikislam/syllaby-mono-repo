<?php

namespace App\Http\Requests\RealClones;

use Illuminate\Validation\Rule;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\RealClones\Enums\RealCloneProvider;

class UpdateRealCloneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): bool
    {
        return $gate->allows('update', $this->route('clone'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'script' => ['sometimes', 'string', 'min:4', 'max:3000'],
            'voice_id' => ['sometimes', 'integer', 'exists:voices,id'],
            'avatar_id' => ['sometimes', 'integer', 'exists:avatars,id'],
            'provider' => ['required_with:avatar_id', 'string', Rule::in(RealCloneProvider::toArray())],
        ];
    }
}
