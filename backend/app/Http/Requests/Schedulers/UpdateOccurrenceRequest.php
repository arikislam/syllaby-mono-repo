<?php

namespace App\Http\Requests\Schedulers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOccurrenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): bool
    {
        return $gate->allows('update', $this->route('occurrence'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'script' => ['sometimes', 'nullable', 'string'],
            'topic' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
