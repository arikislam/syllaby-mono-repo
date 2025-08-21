<?php

namespace App\Syllaby\Subscriptions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JVZooSubscriptionItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are not mass assignable.
     */
    protected $guarded = [];

    /**
     * The table associated with the model.
     */
    protected $table = 'jvzoo_subscription_items';

    /**
     * Get the subscription this item belongs to.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(JVZooSubscription::class, 'jvzoo_subscription_id');
    }

    /**
     * Get the plan for this subscription item.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(JVZooPlan::class);
    }

    /**
     * Get the total amount for this item.
     */
    public function getTotal(): int
    {
        return $this->plan->price * $this->quantity;
    }
}
