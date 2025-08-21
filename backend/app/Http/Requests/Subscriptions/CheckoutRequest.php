<?php

namespace App\Http\Requests\Subscriptions;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! $this->user()->subscribed()) {
            return true;
        }

        $subscription = $this->user()->subscription();

        if ($subscription && ($subscription->recurring() || $subscription->onTrial())) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array>
     */
    public function rules(): array
    {
        return [
            'cancel_url' => ['required', 'url'],
            'success_url' => ['required', 'url'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ];
    }
}
