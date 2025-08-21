<?php

namespace Database\Factories;

use App\Syllaby\Subscriptions\JVZooTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Syllaby\Subscriptions\Enums\JVZooPaymentStatus;
use App\Syllaby\Subscriptions\Enums\JVZooTransactionType;

class JVZooTransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = JVZooTransaction::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $type = fake()->randomElement([
            JVZooTransactionType::SALE,
            JVZooTransactionType::BILL,
            JVZooTransactionType::CANCEL_REBILL,
        ]);

        return [
            'user_id' => null,
            'jvzoo_subscription_id' => null,
            'receipt' => strtoupper(fake()->unique()->bothify('?#?#?#?#?#?#?#?#')),
            'product_id' => fake()->randomElement(['418753', '418754', '418755']),
            'product_name' => fake()->randomElement(['Syllaby 1500 - Monthly', 'Syllaby 3000 - Monthly', 'Syllaby 5000 - Yearly']),
            'product_type' => 'RECURRING',
            'transaction_type' => $type->value,
            'amount' => fake()->randomElement([0, 1900, 3900, 9900]),
            'payment_method' => 'STRP',
            'vendor' => '3344873',
            'affiliate' => '0',
            'customer_email' => fake()->safeEmail(),
            'customer_name' => fake()->name(),
            'customer_state' => fake()->stateAbbr(),
            'customer_country' => fake()->countryCode(),
            'upsell_receipt' => null,
            'affiliate_tracking_id' => null,
            'vendor_through' => null,
            'verification_hash' => strtoupper(fake()->regexify('[A-F0-9]{8}')),
            'status' => $this->mapTransactionTypeToStatus($type),
            'verified_at' => now(),
            'onboarding_token' => null,
            'onboarding_expires_at' => null,
            'onboarding_completed_at' => null,
            'referral_metadata' => [
                'jvzoo_vendor' => null,
                'jvzoo_thirdparty' => null,
                'jvzoo_ipaddress' => fake()->ipv4(),
            ],
            'payload' => [],
        ];
    }

    /**
     * Map transaction type to payment status.
     */
    private function mapTransactionTypeToStatus(JVZooTransactionType $type): string
    {
        return match ($type) {
            JVZooTransactionType::SALE, JVZooTransactionType::BILL => JVZooPaymentStatus::COMPLETED->value,
            JVZooTransactionType::RFND, JVZooTransactionType::CGBK => JVZooPaymentStatus::REFUNDED->value,
            JVZooTransactionType::CANCEL_REBILL => JVZooPaymentStatus::CANCELLED->value,
            JVZooTransactionType::INSF => JVZooPaymentStatus::FAILED->value,
        };
    }

    /**
     * Indicate that the transaction is a sale.
     */
    public function sale(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => JVZooTransactionType::SALE->value,
            'status' => JVZooPaymentStatus::COMPLETED->value,
        ]);
    }

    /**
     * Indicate that the transaction is a recurring bill.
     */
    public function bill(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => JVZooTransactionType::BILL->value,
            'status' => JVZooPaymentStatus::COMPLETED->value,
        ]);
    }

    /**
     * Indicate that the transaction is a cancellation.
     */
    public function cancellation(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => JVZooTransactionType::CANCEL_REBILL->value,
            'status' => JVZooPaymentStatus::CANCELLED->value,
        ]);
    }

    /**
     * Indicate that the transaction is a payment failure.
     */
    public function paymentFailure(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => JVZooTransactionType::INSF->value,
            'status' => JVZooPaymentStatus::FAILED->value,
        ]);
    }

    /**
     * Indicate that the transaction needs onboarding.
     */
    public function withOnboarding(): static
    {
        return $this->state(fn (array $attributes) => [
            'onboarding_token' => bin2hex(random_bytes(32)),
            'onboarding_expires_at' => now()->addHours(48),
            'onboarding_completed_at' => null,
        ]);
    }
}
