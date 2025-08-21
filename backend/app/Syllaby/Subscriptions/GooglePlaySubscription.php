<?php

namespace App\Syllaby\Subscriptions;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Syllaby\Subscriptions\Contracts\SubscriptionContract;

class GooglePlaySubscription extends Model implements SubscriptionContract
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'google_play_subscriptions';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * The relations to eager load on every query.
     */
    protected $with = ['items'];

    /**
     * Status constants
     */
    const string STATUS_ACTIVE = 'active';

    const string STATUS_CANCELED = 'canceled';

    const string STATUS_EXPIRED = 'expired';

    const string STATUS_PAUSED = 'paused';

    const string STATUS_IN_GRACE_PERIOD = 'in_grace_period';

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the owner associated with the subscription.
     */
    public function owner(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Get the subscription items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(GooglePlaySubscriptionItem::class);
    }

    public function getPriceInDollars(): float
    {
        return $this->price_amount_micros / 1000000;
    }

    public function previousSubscription(): ?self
    {
        if (! $this->linked_purchase_token) {
            return null;
        }

        return self::where('purchase_token', $this->linked_purchase_token)->first();
    }

    /**
     * Get the next subscription (for plan changes).
     */
    public function nextSubscription(): ?self
    {
        return self::where('linked_purchase_token', $this->purchase_token)->first();
    }

    /**
     * Check if this is a plan change subscription.
     */
    public function isPlanChange(): bool
    {
        return $this->linked_purchase_token !== null;
    }

    /**
     * Get the subscription end date (compatibility with Stripe subscriptions).
     * Maps expires_at to ends_at for consistency.
     */
    public function getEndsAtAttribute()
    {
        return $this->expires_at;
    }

    /**
     * Get the scheduler ID attribute (compatibility with Stripe subscriptions).
     * For Google Play, return null as this is Stripe-specific.
     */
    public function getSchedulerIdAttribute()
    {
        return null;
    }

    /**
     * Find a subscription item by price or fail.
     */
    public function findItemOrFail($price): mixed
    {
        return $this->items()->where('plan_id', $price)->firstOrFail();
    }

    /**
     * Check if the subscription is active.
     */
    public function active(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Scope a query for active subscriptions.
     */
    public function scopeActive($query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Check if the subscription is on trial.
     */
    public function onTrial(): bool
    {
        if ($this->trial_end_at) {
            return $this->trial_end_at->isFuture();
        }

        return $this->user &&
               $this->user->trial_ends_at &&
               $this->user->trial_ends_at->isFuture();
    }

    /**
     * Scope a query for subscriptions on trial.
     */
    public function scopeOnTrial($query): void
    {
        $query->whereNotNull('trial_end_at')
            ->where('trial_end_at', '>', now());
    }

    /**
     * Cancel the subscription immediately (compatibility with Stripe subscriptions).
     */
    public function cancelNow(): void
    {
        $this->cancel();
    }

    /**
     * Check if the subscription is canceled.
     */
    public function canceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    /**
     * Scope a query for canceled subscriptions.
     */
    public function scopeCanceled($query): void
    {
        $query->where('status', self::STATUS_CANCELED);
    }

    /**
     * Scope a query for subscriptions that are not canceled.
     */
    public function scopeNotCanceled($query): void
    {
        $query->where('status', '!=', self::STATUS_CANCELED);
    }

    /**
     * Check if the subscription is on grace period.
     */
    public function onGracePeriod(): bool
    {
        return $this->status === self::STATUS_IN_GRACE_PERIOD;
    }

    /**
     * Scope a query for subscriptions on grace period.
     */
    public function scopeOnGracePeriod($query): void
    {
        $query->where('status', self::STATUS_IN_GRACE_PERIOD);
    }

    /**
     * Scope a query for subscriptions not on grace period.
     */
    public function scopeNotOnGracePeriod($query): void
    {
        $query->where('status', '!=', self::STATUS_IN_GRACE_PERIOD);
    }

    /**
     * Check if the subscription is recurring.
     */
    public function recurring(): bool
    {
        return $this->active() && $this->auto_renewing;
    }

    /**
     * Scope a query for recurring subscriptions.
     */
    public function scopeRecurring($query): void
    {
        $query->where('status', self::STATUS_ACTIVE)
            ->where('auto_renewing', true);
    }

    /**
     * Check if the subscription is incomplete.
     */
    public function incomplete(): bool
    {
        return false;
    }

    /**
     * Scope a query for incomplete subscriptions.
     */
    public function scopeIncomplete($query): void
    {
        $query->whereRaw('1 = 0'); // Always empty for Google Play
    }

    /**
     * Check if the subscription is past due.
     */
    public function pastDue(): bool
    {
        return $this->status === self::STATUS_IN_GRACE_PERIOD;
    }

    /**
     * Scope a query for past due subscriptions.
     */
    public function scopePastDue($query): void
    {
        $query->where('status', self::STATUS_IN_GRACE_PERIOD);
    }

    /**
     * Check if the subscription has ended and the grace period has expired.
     */
    public function ended(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
               ($this->expires_at && $this->expires_at->isPast());
    }

    /**
     * Scope a query for ended subscriptions.
     */
    public function scopeEnded($query): void
    {
        $query->where('status', self::STATUS_EXPIRED)->orWhere(function ($q) {
            $q->whereNotNull('expires_at')->where('expires_at', '<=', now());
        });
    }

    /**
     * Check if the subscription's trial has expired.
     */
    public function hasExpiredTrial(): bool
    {
        if ($this->trial_end_at) {
            return $this->trial_end_at->isPast();
        }

        return $this->user &&
               $this->user->trial_ends_at &&
               $this->user->trial_ends_at->isPast();
    }

    /**
     * Scope a query for subscriptions with expired trial.
     */
    public function scopeExpiredTrial($query): void
    {
        $query->whereNotNull('trial_end_at')->where('trial_end_at', '<', now());
    }

    /**
     * Scope a query for subscriptions that are not on trial.
     */
    public function scopeNotOnTrial($query): void
    {
        $query->whereNull('trial_end_at')->orWhere('trial_end_at', '<=', now());
    }

    /**
     * Get the trial end date.
     */
    public function trialEndsAt(): ?Carbon
    {
        return $this->trial_end_at ?: $this->user?->trial_ends_at;
    }

    /**
     * Get the subscription's end date.
     */
    public function endsAt(): ?Carbon
    {
        return $this->expires_at;
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(): self
    {
        $this->update([
            'status' => self::STATUS_CANCELED,
            'canceled_at' => now(),
            'auto_renewing' => false,
        ]);

        return $this;
    }

    /**
     * Get the plan ID associated with this subscription.
     */
    public function planId(): mixed
    {
        return $this->plan_id;
    }

    /**
     * Check if the subscription has a specific plan.
     */
    public function hasPrice(mixed $plan): bool
    {
        return $this->plan_id === $plan;
    }

    /**
     * Check if the subscription is valid (active, on trial, or on grace period).
     */
    public function valid(): bool
    {
        return $this->active() || $this->onTrial() || $this->onGracePeriod();
    }

    /**
     * Check if the subscription has incomplete payment.
     */
    public function hasIncompletePayment(): bool
    {
        return false;
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'paused_at' => 'datetime',
            'resumed_at' => 'datetime',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'canceled_at' => 'datetime',
            'auto_renewing' => 'boolean',
            'acknowledgement_state' => 'array',
            'grace_period_expires_at' => 'datetime',
            'free_trial_period' => 'array',
            'intro_price_info' => 'array',
            'trial_start_at' => 'datetime',
            'trial_end_at' => 'datetime',
        ];
    }
}
