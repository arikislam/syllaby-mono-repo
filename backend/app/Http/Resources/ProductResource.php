<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Plan */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'is_active' => $this->active,
            'metadata' => $this->meta,
            'prices' => PriceResource::collection($this->whenLoaded('prices')),
            'created_at' => $this->created_at->toJson(),
            'updated_at' => $this->updated_at->toJson(),

            // Google Play Console fields
            'google_play' => GooglePlayPlanResource::make($this->whenLoaded('googlePlayPlan')),
        ];
    }
}
