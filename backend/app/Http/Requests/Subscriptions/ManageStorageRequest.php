<?php

namespace App\Http\Requests\Subscriptions;

use Illuminate\Validation\Rule;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;

class ManageStorageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): bool
    {
        return $gate->allows('storage', [$this->user()->plan, $this->input('quantity')]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', Rule::in([5, 10, 20, 50])],
        ];
    }
}
