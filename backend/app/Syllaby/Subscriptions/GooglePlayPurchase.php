<?php

namespace App\Syllaby\Subscriptions;

use App\Syllaby\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GooglePlayPurchase extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'google_play_purchases';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'purchase_token',
        'order_id',
        'product_id',
        'plan_id',
        'quantity',
        'status',
        'purchased_at',
        'acknowledged_at',
        'consumed_at',
        'refunded_at',
        'price_amount_micros',
        'price_currency_code',
        'country_code',
        'purchase_state',
        'consumption_state',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'purchased_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'consumed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'quantity' => 'integer',
        'price_amount_micros' => 'integer',
        'purchase_state' => 'integer',
        'consumption_state' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Status constants
     */
    const STATUS_PURCHASED = 'purchased';

    const STATUS_ACKNOWLEDGED = 'acknowledged';

    const STATUS_CONSUMED = 'consumed';

    const STATUS_REFUNDED = 'refunded';

    const STATUS_PENDING = 'pending';

    /**
     * Purchase state constants (from Google Play)
     */
    const PURCHASE_STATE_PURCHASED = 0;

    const PURCHASE_STATE_CANCELED = 1;

    const PURCHASE_STATE_PENDING = 2;

    /**
     * Get the user that owns the purchase.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan associated with the purchase.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Scope a query to only include purchased items.
     */
    public function scopePurchased($query)
    {
        return $query->where('status', self::STATUS_PURCHASED);
    }

    /**
     * Scope a query to only include acknowledged purchases.
     */
    public function scopeAcknowledged($query)
    {
        return $query->where('status', self::STATUS_ACKNOWLEDGED);
    }

    /**
     * Scope a query to only include refunded purchases.
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    /**
     * Scope a query to only include consumed purchases.
     */
    public function scopeConsumed($query)
    {
        return $query->where('status', self::STATUS_CONSUMED);
    }

    /**
     * Scope a query to only include not consumed purchases.
     */
    public function scopeNotConsumed($query)
    {
        return $query->where('status', '!=', self::STATUS_CONSUMED);
    }

    /**
     * Check if the purchase is refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /**
     * Check if the purchase is acknowledged.
     */
    public function isAcknowledged(): bool
    {
        return $this->status === self::STATUS_ACKNOWLEDGED ||
               $this->acknowledged_at !== null;
    }

    /**
     * Check if the purchase is consumed.
     */
    public function isConsumed(): bool
    {
        return $this->status === self::STATUS_CONSUMED ||
               $this->consumed_at !== null;
    }

    /**
     * Acknowledge the purchase.
     */
    public function acknowledge(): void
    {
        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * Consume the purchase.
     */
    public function consume(): void
    {
        $this->update([
            'status' => self::STATUS_CONSUMED,
            'consumed_at' => now(),
        ]);
    }

    /**
     * Mark as refunded.
     */
    public function refund(): void
    {
        $this->update([
            'status' => self::STATUS_REFUNDED,
            'refunded_at' => now(),
        ]);
    }

    /**
     * Get the price in dollars.
     */
    public function getPriceInDollars(): float
    {
        return $this->price_amount_micros / 1000000;
    }

    /**
     * Get the total price in dollars (price * quantity).
     */
    public function getTotalPriceInDollars(): float
    {
        return $this->getPriceInDollars() * $this->quantity;
    }
}
