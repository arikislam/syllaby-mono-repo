<?php

namespace App\Http\Requests\Subscriptions;

use Illuminate\Foundation\Http\FormRequest;

class CreatePurchaseRequest extends FormRequest
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
            'context' => ['array'],
            'price_id' => ['required', 'integer', 'exists:plans,id'],
            'cancel_url' => ['required', 'url'],
            'success_url' => ['required', 'url'],
        ];
    }
}
