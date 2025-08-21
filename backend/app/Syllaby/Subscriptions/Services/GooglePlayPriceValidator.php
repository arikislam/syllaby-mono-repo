<?php

namespace App\Syllaby\Subscriptions\Services;

use Exception;

class GooglePlayPriceValidator
{
    private const int MIN_PRICE_MICROS = 990000; // $0.99

    private const int MAX_PRICE_MICROS = 999990000; // $999.99

    /**
     * @throws Exception
     */
    public function validate(int $priceMicros, int $planId): void
    {
        if ($priceMicros < self::MIN_PRICE_MICROS) {
            throw new Exception(
                "Price is below Google Play minimum of $0.99. Plan ID: {$planId}, Price: $".
                number_format($priceMicros / 1000000, 2)
            );
        }

        if ($priceMicros > self::MAX_PRICE_MICROS) {
            throw new Exception(
                "Price is above Google Play maximum of $999. Plan ID: {$planId}, Price: $".
                number_format($priceMicros / 1000000, 2)
            );
        }
    }
}
