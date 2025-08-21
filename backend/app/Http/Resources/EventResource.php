<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Videos\Video;
use App\Syllaby\Planner\Event;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Database\Eloquent\Relations\Relation;

/** @mixin Event */
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'scheduler_id' => $this->scheduler_id,
            'color' => $this->color,
            'model_id' => $this->model_id,
            'model_type' => $this->model_type,
            'starts_at' => $this->starts_at->toJSON(),
            'ends_at' => $this->ends_at->toJSON(),
            'completed_at' => $this->completed_at?->toJSON(),
            'cancelled_at' => $this->cancelled_at?->toJSON(),
            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),

            'user' => UserResource::make($this->whenLoaded('user')),
            $this->model_type => $this->whenLoaded('model', $this->resolveModel()),
            'scheduler' => SchedulerResource::make($this->whenLoaded('scheduler')),
        ];
    }

    private function resolveModel(): callable
    {
        return fn () => match ($this->model_type) {
            Relation::getMorphAlias(Video::class) => VideoResource::make($this->model),
            Relation::getMorphAlias(Publication::class) => PublicationResource::make($this->model),
            default => null
        };
    }
}
