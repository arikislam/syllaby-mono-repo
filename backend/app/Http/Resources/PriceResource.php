<?php

namespace App\Http\Resources;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Plan */
class PriceResource extends JsonResource
{
    protected array $details;

    protected User $user;

    /**
     * Create new resource instance.
     */
    public function __construct(Plan $plan)
    {
        parent::__construct($plan);

        $this->details = $plan->details();
        $this->user = auth('sanctum')->user();
    }

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'discount' => $this->discount(),
            'amount' => [
                'raw' => (int) $this->price,
                'formatted' => Cashier::formatAmount($this->price, null, null, ['min_fraction_digits' => 0]),
            ],
            'trial_days' => $this->trialDays($this->user),
            'currency' => $this->currency,
            'is_active' => $this->active,
            'metadata' => $this->meta,
            'features' => Arr::get($this->details, 'features', []),

            'google_play' => GooglePlayPlanResource::make($this->whenLoaded('googlePlayPlan')),

            'jvzoo' => $this->whenLoaded('jvzoo', fn () => [
                'jvzoo_id' => $this->jvzoo->jvzoo_id,
                'is_active' => $this->jvzoo->is_active,
                'metadata' => $this->jvzoo->metadata,
                'created_at' => $this->jvzoo->created_at->toJson(),
                'updated_at' => $this->jvzoo->updated_at->toJson(),
            ]),

            'created_at' => $this->created_at->toJson(),
            'updated_at' => $this->updated_at->toJson(),
        ];
    }

    /**
     * Plan discount amount.
     */
    private function discount(): float
    {
        return match ($this->type) {
            'one_time', 'month' => 1,
            'year' => 0.15,
            default => 0
        };
    }
}
