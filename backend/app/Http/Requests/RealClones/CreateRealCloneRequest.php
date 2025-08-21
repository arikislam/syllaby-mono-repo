<?php

namespace App\Http\Requests\RealClones;

use Illuminate\Validation\Rule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\RealClones\Enums\RealCloneProvider;

class CreateRealCloneRequest extends FormRequest
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
            'voice_id' => ['sometimes', 'integer', 'exists:voices,id'],
            'avatar_id' => ['sometimes', 'integer', 'exists:avatars,id'],
            'provider' => ['sometimes', 'required_with:avatar_id', 'string', Rule::in(RealCloneProvider::toArray())],
            'footage_id' => ['required', 'integer', Rule::exists('footages', 'id')->where(function (Builder $query) {
                return $query->where('user_id', auth()->id());
            })],
        ];
    }
}
