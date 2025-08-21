<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Subscriptions\GooglePlayPlan;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GooglePlayPlan|null */
class GooglePlayPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var GooglePlayPlan|null $plan */
        $plan = $this->resource;
        $available = ! empty($plan->metadata);

        if (! $plan || ! $available) {
            // Relation missing or not available
            return [];
        }

        return [
            'available' => $available,
            'sku' => $plan->product_id,
            'package_name' => config('google-play.package_name'),
            'status' => $plan->status,
            'purchase_type' => $plan->product_type === 'subscription' ? 'subscription' : 'managedUser',
            'product_id' => $plan->product_id,
            'product_type' => $plan->product_type,
            'subscription_period' => $plan->billing_period,
            'base_plan_id' => $plan->base_plan_id,
            'price' => [
                'micros' => $plan->price_micros,
                'currency' => $plan->currency_code,
                'formatted' => $plan->formatted_price ?? ('$'.number_format($plan->price_in_dollars, 2)),
                'dollars' => $plan->price_in_dollars,
            ],
            'is_synced' => ! is_null($plan->updated_at),
            'last_synced_at' => $plan->updated_at?->toJson(),
            'metadata' => $plan->metadata,
            'plan_id' => $plan->plan_id,
        ];
    }
}
