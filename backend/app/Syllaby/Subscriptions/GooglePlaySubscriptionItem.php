<?php

namespace App\Syllaby\Subscriptions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GooglePlaySubscriptionItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'google_play_subscription_items';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'google_play_subscription_id',
        'plan_id',
        'product_id',
        'quantity',
        'price_amount_micros',
        'price_currency_code',
        'billing_period',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity' => 'integer',
        'price_amount_micros' => 'integer',
    ];

    /**
     * Get the subscription that owns this item.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(GooglePlaySubscription::class, 'google_play_subscription_id');
    }

    /**
     * Get the plan associated with this item.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
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
