<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Videos\Footage;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Footage */
class FootageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'video_id' => $this->video_id,
            'hash' => $this->hash,
            'preference' => $this->preference,

            'video' => VideoResource::make($this->whenLoaded('video')),
            'source' => TimelineResource::make($this->whenLoaded('timeline')),
            'real_clones' => RealCloneResource::collection($this->whenLoaded('clones')),

            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }
}
