<?php

namespace App\Syllaby\Subscriptions\Rules;

use Closure;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\InvalidRequestException;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCoupon implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $coupon = Cashier::stripe()->coupons->retrieve($value);
            $coupon->valid ?: $fail('Coupon is invalid');
        } catch (InvalidRequestException $error) {
            $fail('Coupon does not exist');
        } catch (ApiErrorException $error) {
            $fail($error->getMessage());
        }
    }
}
