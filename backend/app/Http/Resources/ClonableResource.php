<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\RealClones\Avatar;
use App\Syllaby\Clonables\Clonable;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Clonable */
class ClonableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'purchase_id' => $this->purchase_id,
            'model_id' => $this->model_id,
            'model_type' => $this->model_type,
            'status' => $this->status,
            'metadata' => $this->metadata,

            'model' => $this->whenLoaded('model', fn () => match ($this->model_type) {
                (new Voice)->getMorphClass() => VoiceResource::make($this->model),
                (new Avatar)->getMorphClass() => AvatarResource::make($this->model),
                default => null
            }),

            'media' => MediaResource::collection($this->whenLoaded('media')),

            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }
}
