<?php

namespace App\Http\Requests\Authentication;

use App\Syllaby\Users\User;
use App\Syllaby\Auth\Helpers\Utility;
use App\Syllaby\Auth\Rules\TrustedEmail;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Subscriptions\Enums\SubscriptionProvider;

/** @mixin User */
class RegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['bail', 'required', 'email:filter,dns', 'max:255', 'unique:users,email', new TrustedEmail],
            'password' => ['required', 'max:48', Utility::setPasswordRules()],
            'registration_code' => ['nullable', 'string', 'max:255'],
            'newsletter' => ['nullable', 'boolean'],
            'promo_code' => ['sometimes', 'nullable', 'string'],
            'source' => ['nullable', 'string', 'in:'.implode(',', SubscriptionProvider::getSources())],
            'pm_exemption_code' => ['sometimes', 'nullable', 'string', 'max:26'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            ...$this->shouldExemptPaymentMethod(),
        ]);
    }

    /**
     * Determine if the payment method should be exempt.
     */
    private function shouldExemptPaymentMethod(): array
    {
        if (blank($this->input('promo_code'))) {
            return ['pm_exemption_code' => null, 'promo_code' => null];
        }

        $campaigns = explode(',', config('syllaby.campaigns'));

        if (in_array($this->input('promo_code'), $campaigns)) {
            return ['pm_exemption_code' => $this->input('promo_code'), 'promo_code' => null];
        }

        return ['pm_exemption_code' => null, 'promo_code' => $this->input('promo_code')];
    }
}
