<?php

namespace App\Syllaby\Subscriptions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'active' => 'boolean',
        ];
    }

    /**
     * Gets the active plans only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', 1);
    }

    /**
     * Gets recurring plans only.
     */
    public function scopeRecurring(Builder $query)
    {
        return $query->whereIn('type', ['month', 'year']);
    }

    public function scopeProduct(Builder $query)
    {
        return $query->where('type', 'product')->whereNull('parent_id');
    }

    /**
     * Gets one_time plans only.
     */
    public function scopeOneTime(Builder $query)
    {
        return $query->where('type', 'one_time');
    }

    /**
     * Gets plans that need to be synced to Google Play
     */
    public function scopeNeedsGooglePlaySync(Builder $query)
    {
        return $query->where(function ($q) {
            $q->whereHas('googlePlayPlan', function ($subQ) {
                $subQ->where('status', '!=', 'active');
            })
                ->orWhereDoesntHave('googlePlayPlan');
        });
    }

    /**
     * Scope for plans with Google Play data
     */
    public function scopeGooglePlayProducts(Builder $query): Builder
    {
        return $query->whereHas('googlePlayPlan');
    }

    /**
     * Gets the parent plan.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'parent_id');
    }

    /**
     * Gets all children plans.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(Plan::class, 'parent_id');
    }

    /**
     * Given plan details.
     */
    public function details(?string $key = null, ?string $default = null): mixed
    {
        if ($this->type === 'one_time' || $this->type === 'product') {
            return [];
        }

        $this->loadMissing('product');

        // Handle case where product relation doesn't exist
        if (! $this->product) {
            return blank($key) ? [] : $default;
        }

        $productId = $this->product->plan_id;

        $details = Arr::first(config('syllaby.plans'), function ($plan) use ($productId) {
            return $productId === Arr::get($plan, 'product_id');
        });

        if (blank($details)) {
            return blank($key) ? [] : $default;
        }

        return blank($key) ? $details : Arr::get($details, $key, $default);
    }

    /**
     * User trial days.
     */
    public function trialDays(User $user): int
    {
        if ($this->type === 'one_time') {
            return 0;
        }

        return match ($user->registration_code) {
            'trial30' => 30,
            'trial14' => 14,
            default => (int) $this->details('trial_days', 7)
        };
    }

    /**
     * Gets all the purchases that were made for the current plan.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return PlanFactory::new();
    }

    // Google Play specific methods

    /**
     * Check if plan needs Google Play sync
     */
    public function needsGooglePlaySync(): bool
    {
        return ! $this->googlePlayPlan ||
               ($this->googlePlayPlan && $this->updated_at > $this->googlePlayPlan->updated_at);
    }

    /**
     * Get the Google Play plan associated with this plan.
     */
    public function googlePlayPlan(): HasOne
    {
        return $this->hasOne(GooglePlayPlan::class, 'plan_id');
    }

    /**
     * Get the Jvzoo plan associated with this plan.
     */
    public function jvzoo(): HasOne
    {
        return $this->hasOne(JVZooPlan::class, 'plan_id', 'id');
    }

    /**
     * Get the Google Play Real-Time Developer Notifications for this plan.
     */
    public function googlePlayRtdns(): HasMany
    {
        return $this->hasMany(GooglePlayRtdn::class, 'plan_id');
    }

    /**
     * Get Google Play SKU accessor (for convenience)
     */
    public function getGooglePlaySkuAttribute(): ?string
    {
        return $this->googlePlayPlan?->product_id;
    }
}
