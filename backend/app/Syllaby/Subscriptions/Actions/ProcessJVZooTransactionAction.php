<?php

namespace App\Syllaby\Subscriptions\Actions;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use App\Syllaby\Subscriptions\Plan;
use App\Syllaby\Subscriptions\JVZooPlan;
use App\Syllaby\Subscriptions\JVZooTransaction;
use App\Syllaby\Subscriptions\Enums\JVZooPaymentStatus;
use App\Syllaby\Subscriptions\Enums\JVZooTransactionType;

class ProcessJVZooTransactionAction
{
    /**
     * Process a JVZoo purchase from webhook payload.
     */
    public function handle(array $payload): JVZooTransaction
    {
        $transactionType = Arr::get($payload, 'ctransaction');
        $productId = Arr::get($payload, 'cproditem');

        $this->findOrCreateJVZooPlan($productId, $payload);

        return JVZooTransaction::create([
            'receipt' => Arr::get($payload, 'ctransreceipt'),
            'product_id' => $productId,
            'product_name' => Arr::get($payload, 'cprodtitle'),
            'product_type' => Arr::get($payload, 'cprodtype'),
            'transaction_type' => $transactionType,
            'amount' => $this->toCents($payload, 'ctransamount'),
            'payment_method' => Arr::get($payload, 'ctranspaymentmethod'),
            'vendor' => Arr::get($payload, 'ctransvendor'),
            'affiliate' => Arr::get($payload, 'ctransaffiliate'),
            'customer_email' => Arr::get($payload, 'ccustemail'),
            'customer_name' => Arr::get($payload, 'ccustname'),
            'customer_state' => Arr::get($payload, 'ccuststate'),
            'customer_country' => Arr::get($payload, 'ccustcc'),
            'upsell_receipt' => Arr::get($payload, 'cupsellreceipt'),
            'affiliate_tracking_id' => Arr::get($payload, 'caffitid'),
            'vendor_through' => Arr::get($payload, 'cvendthru'),
            'verification_hash' => Arr::get($payload, 'cverify'),
            'status' => $this->mapTransactionTypeToStatus($transactionType),
            'verified_at' => now(),
            'referral_metadata' => $this->extractReferralData($payload),
            'payload' => $payload,
        ]);
    }

    /**
     * Extract referral and tracking data from payload.
     */
    private function extractReferralData(array $payload): array
    {
        return [
            'jvzoo_vendor' => Arr::get($payload, 'cvendor'),
            'jvzoo_thirdparty' => Arr::get($payload, 'cthirdparty'),
            'jvzoo_ipaddress' => Arr::get($payload, 'cipaddress'),
        ];
    }

    /**
     * Find or create JVZoo plan for the product.
     */
    private function findOrCreateJVZooPlan(string $product, array $payload): void
    {
        if (! $stripeId = Arr::get(config('services.jvzoo.plans'), $product)) {
            throw new Exception("JVZoo plan not found for product ID: {$product}");
        }

        if (JVZooPlan::where('jvzoo_id', $product)->exists()) {
            return;
        }

        if (! $plan = Plan::where('plan_id', $stripeId)->first()) {
            throw new Exception("Stripe plan not found for plan ID: {$stripeId}");
        }

        $price = $this->toCents($payload, 'ctransamount');

        JVZooPlan::create([
            'is_active' => true,
            'jvzoo_id' => $product,
            'plan_id' => $plan->id,
            'interval' => $plan->type,
            'price' => $price === 0 ? $plan->price : $price,
            'currency' => Arr::get($payload, 'ccurrency', 'USD'),
            'name' => Arr::get($payload, 'cprodtitle', "JVZoo Product {$product}"),
            'metadata' => [
                'created_from_webhook' => true,
                'full_credits' => Arr::get($plan->meta, 'full_credits', 500),
                'trial_credits' => Arr::get($plan->meta, 'trial_credits', 50),
            ],
        ]);
    }

    /**
     * Map JVZoo transaction type to purchase status.
     */
    private function mapTransactionTypeToStatus(string $type): string
    {
        return match ($type) {
            JVZooTransactionType::SALE->value => JVZooPaymentStatus::COMPLETED->value,
            JVZooTransactionType::BILL->value => JVZooPaymentStatus::COMPLETED->value,
            JVZooTransactionType::RFND->value => JVZooPaymentStatus::REFUNDED->value,
            JVZooTransactionType::CGBK->value => JVZooPaymentStatus::REFUNDED->value,
            JVZooTransactionType::CANCEL_REBILL->value => JVZooPaymentStatus::CANCELLED->value,
            JVZooTransactionType::INSF->value => JVZooPaymentStatus::FAILED->value,
            default => JVZooPaymentStatus::PENDING->value,
        };
    }

    /**
     * Convert amount to cents.
     */
    private function toCents(array $payload, string $key): int
    {
        return (int) (Arr::get($payload, $key, 0) * 100);
    }
}
