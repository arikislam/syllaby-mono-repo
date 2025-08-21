<?php

namespace App\Syllaby\Subscriptions;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\JVZooSubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Syllaby\Subscriptions\Contracts\SubscriptionContract;

class JVZooSubscription extends Model implements SubscriptionContract
{
    use HasFactory;

    protected $guarded = [];

    /**
     * The relations to eager load on every query.
     */
    protected $with = ['items'];

    /**
     * The table associated with the model.
     */
    protected $table = 'jvzoo_subscriptions';

    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_CANCELED = 'canceled';

    public const string STATUS_EXPIRED = 'expired';

    public const string STATUS_TRIAL = 'trialing';

    /**
     * Get the user that owns the subscription.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user associated with the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->owner();
    }

    /**
     * Get the JVZoo plan for this subscription.
     */
    public function jvzooPlan(): BelongsTo
    {
        return $this->belongsTo(JVZooPlan::class);
    }

    /**
     * Get the subscription items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(JVZooSubscriptionItem::class, 'jvzoo_subscription_id');
    }

    /**
     * Find a subscription item by price (actually plan id) or fail.
     */
    public function findItemOrFail($price): mixed
    {
        return $this->items()->where('jvzoo_plan_id', $price)->firstOrFail();
    }

    /**
     * Get the related purchases.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(JVZooTransaction::class, 'jvzoo_subscription_id');
    }

    /**
     * Check if the subscription is active.
     */
    public function active(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the subscription is on trial.
     */
    public function onTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the subscription is canceled.
     */
    public function canceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    /**
     * Check if the subscription is expired.
     */
    public function expired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->ends_at && $this->ends_at->isPast());
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(): self
    {
        $this->status = self::STATUS_CANCELED;
        $this->ends_at = now();
        $this->save();

        return $this;
    }

    /**
     * A subscription is on grace period when it's cancelled but the access hasn't expired yet.
     */
    public function onGracePeriod(): bool
    {
        return $this->canceled() && $this->ends_at && $this->ends_at->isFuture();
    }

    /**
     * Get the subscription's trial end date.
     */
    public function trialEndsAt(): ?Carbon
    {
        return $this->trial_ends_at;
    }

    /**
     * Get the subscription's end date.
     */
    public function endsAt(): ?Carbon
    {
        return $this->ends_at;
    }

    /**
     * Get the plan ID associated with this subscription.
     */
    public function planId(): mixed
    {
        return $this->jvzoo_plan_id;
    }

    /**
     * Check if the subscription has a specific plan.
     */
    public function hasPrice(mixed $plan): bool
    {
        return $this->jvzoo_plan_id === $plan;
    }

    /**
     * Check if the subscription is valid (active, on trial, or on grace period).
     */
    public function valid(): bool
    {
        return $this->active() || $this->onTrial() || $this->onGracePeriod();
    }

    /**
     * Check if the subscription is incomplete.
     */
    public function incomplete(): bool
    {
        return is_null($this->jvzoo_plan_id) || is_null($this->status);
    }

    /**
     * Check if the subscription is past due.
     */
    public function pastDue(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Check if the subscription has ended and the grace period has expired.
     */
    public function ended(): bool
    {
        return $this->canceled() && ! $this->onGracePeriod();
    }

    /**
     * Check if the subscription's trial has expired.
     */
    public function hasExpiredTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Check if the subscription is recurring.
     * A subscription is recurring when it's actively billing (not on trial, not canceled).
     */
    public function recurring(): bool
    {
        return ! $this->onTrial() && ! $this->canceled();
    }

    /**
     * Check if the subscription has incomplete payment.
     */
    public function hasIncompletePayment(): bool
    {
        return false;
    }

    /**
     * Get subscription by JVZoo receipt.
     */
    public static function findByReceipt(string $receipt): ?self
    {
        return static::where('receipt', $receipt)->first();
    }

    /**
     * Scope to only active subscriptions.
     */
    public function scopeActive($query): void
    {
        $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    /**
     * Filter query by canceled.
     */
    public function scopeCanceled($query): void
    {
        $query->whereNotNull('ends_at');
    }

    /**
     * Filter query by not canceled.
     */
    public function scopeNotCanceled($query): void
    {
        $query->whereNull('ends_at');
    }

    /**
     * Filter query by ended.
     */
    public function scopeEnded($query): void
    {
        $query->canceled()->notOnGracePeriod();
    }

    /**
     * Filter query by incomplete.
     * For JVZoo, this would be subscriptions that failed to complete setup.
     */
    public function scopeIncomplete($query): void
    {
        $query->where(function ($query) {
            $query->whereNull('jvzoo_plan_id')->orWhereNull('status');
        });
    }

    /**
     * Filter query by on trial.
     */
    public function scopeOnTrial($query): void
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', now());
    }

    /**
     * Filter query by not on trial.
     */
    public function scopeNotOnTrial($query): void
    {
        $query->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', now());
    }

    /**
     * Filter query by on grace period.
     */
    public function scopeOnGracePeriod($query): void
    {
        $query->whereNotNull('ends_at')->where('ends_at', '>', now());
    }

    /**
     * Filter query by not on grace period.
     */
    public function scopeNotOnGracePeriod($query): void
    {
        $query->whereNull('ends_at')->orWhere('ends_at', '<=', now());
    }

    /**
     * Filter query by past due.
     * For JVZoo, this would be expired subscriptions that haven't been renewed.
     */
    public function scopePastDue($query): void
    {
        $query->where('status', self::STATUS_EXPIRED);
    }

    /**
     * Filter query by recurring.
     */
    public function scopeRecurring($query): void
    {
        $query->notOnTrial()->notCanceled();
    }

    /**
     * Filter query by subscriptions with expired trial.
     */
    public function scopeExpiredTrial($query): void
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '<', now());
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'ends_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return JVZooSubscriptionFactory::new();
    }
}
