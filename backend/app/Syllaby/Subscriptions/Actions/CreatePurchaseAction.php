<?php

namespace App\Syllaby\Subscriptions\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Subscriptions\Plan;
use App\Syllaby\Subscriptions\Purchase;

class CreatePurchaseAction
{
    /**
     * Creates a new purchase record in storage.
     */
    public function handle(User $user, Plan $product, array $data): Purchase
    {
        return $product->purchases()->create([
            'user_id' => $user->id,
            'status' => Arr::get($data, 'status'),
            'payment_intent' => Arr::get($data, 'payment_intent'),
        ]);
    }
}
