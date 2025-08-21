<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Trackers\Tracker;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tracker */
class TrackerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'trackable_id' => $this->trackable_id,
            'trackable_type' => $this->trackable_type,
            'name' => $this->name,
            'count' => $this->count,
            'limit' => $this->limit,
            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }
}
