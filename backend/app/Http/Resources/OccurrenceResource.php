<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Schedulers\Occurrence;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Occurrence */
class OccurrenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'scheduler_id' => $this->scheduler_id,
            'topic' => $this->topic,
            'script' => $this->script,
            'status' => $this->status,
            'occurs_at' => $this->occurs_at?->toJSON(),
            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),

            'scheduler' => SchedulerResource::make($this->whenLoaded('scheduler')),
        ];
    }
}
