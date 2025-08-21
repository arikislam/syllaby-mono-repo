<?php

namespace App\Syllaby\Subscriptions;

use App\Syllaby\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coupon extends Model
{
    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    /**
     * Get all users who redeem the coupon.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'redemptions')
            ->as('redemptions')
            ->withTimestamps();
    }
}
