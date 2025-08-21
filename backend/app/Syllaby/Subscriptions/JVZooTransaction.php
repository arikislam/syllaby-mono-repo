<?php

namespace App\Syllaby\Subscriptions;

use App\Syllaby\Users\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\JVZooTransactionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Syllaby\Subscriptions\Enums\JVZooPaymentStatus;
use App\Syllaby\Subscriptions\Enums\JVZooTransactionType;

class JVZooTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are not mass assignable.
     */
    protected $guarded = [];

    /**
     * The table associated with the model.
     */
    protected $table = 'jvzoo_transactions';

    /**
     * Get the user associated with this purchase.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the JVZoo subscription associated with this purchase.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(JVZooSubscription::class, 'jvzoo_subscription_id');
    }

    /**
     * Get the JVZoo plan for this purchase.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(JVZooPlan::class, 'jvzoo_plan_id');
    }

    /**
     * Check if this purchase has been verified via IPN.
     */
    public function isVerified(): bool
    {
        return ! is_null($this->verified_at);
    }

    /**
     * Check if onboarding has been completed.
     */
    public function isOnboardingCompleted(): bool
    {
        return ! is_null($this->onboarding_completed_at);
    }

    /**
     * Check if this is a successful sale.
     */
    public function isSuccessfulSale(): bool
    {
        return $this->transaction_type === JVZooTransactionType::SALE->value
            && $this->status === JVZooPaymentStatus::COMPLETED->value;
    }

    /**
     * Check if this is a refund.
     */
    public function isRefund(): bool
    {
        return in_array($this->transaction_type, [
            JVZooTransactionType::RFND->value,
            JVZooTransactionType::CGBK->value,
        ]);
    }

    /**
     * Generate a secure onboarding token.
     */
    public function generateOnboardingToken(): string
    {
        if (! $this->onboarding_token) {
            $this->onboarding_token = Str::random(64);
            $this->save();
        }

        return $this->onboarding_token;
    }

    /**
     * Mark IPN as verified.
     */
    public function markAsVerified(): self
    {
        $this->verified_at = now();
        $this->save();

        return $this;
    }

    /**
     * Mark onboarding as completed.
     */
    public function markOnboardingCompleted(): self
    {
        $this->onboarding_completed_at = now();
        $this->save();

        return $this;
    }

    /**
     * Find purchase by transaction receipt.
     */
    public static function findByTransactionReceipt(string $transactionReceipt): ?self
    {
        return static::where('receipt', $transactionReceipt)->first();
    }

    /**
     * Find purchase by onboarding token.
     */
    public static function findByOnboardingToken(string $token): ?self
    {
        return static::where('onboarding_token', $token)
            ->whereNull('onboarding_completed_at')
            ->first();
    }

    /**
     * Get purchases that need onboarding.
     */
    public function scopePendingOnboarding($query)
    {
        return $query->whereNotNull('verified_at')
            ->whereNull('onboarding_completed_at')
            ->whereNull('user_id');
    }

    /**
     * Get verified purchases.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'verified_at' => 'datetime',
            'referral_metadata' => 'array',
            'onboarding_expires_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return JVZooTransactionFactory::new();
    }
}
