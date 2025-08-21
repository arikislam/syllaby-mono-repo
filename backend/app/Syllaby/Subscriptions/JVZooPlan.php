<?php

namespace App\Syllaby\Subscriptions;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\JVZooPlanFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JVZooPlan extends Model
{
    use HasFactory;

    /**
     * The attributes that are not mass assignable.
     */
    protected $guarded = [];

    /**
     * The table associated with the model.
     */
    protected $table = 'jvzoo_plans';

    /**
     * Get the Stripe plan associated with this Google Play plan
     */
    public function stripePlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * Get all JVZoo subscriptions for this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(JVZooSubscription::class);
    }

    /**
     * Get all JVZoo subscription items for this plan.
     */
    public function subscriptionItems(): HasMany
    {
        return $this->hasMany(JVZooSubscriptionItem::class);
    }

    /**
     * Get all JVZoo purchases for this plan.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(JVZooPurchase::class);
    }

    /**
     * Scope to only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Find a JVZoo plan by product ID.
     */
    public static function findByProductId(string $productId): ?self
    {
        return static::where('jvzoo_id', $productId)->first();
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return JVZooPlanFactory::new();
    }
}
