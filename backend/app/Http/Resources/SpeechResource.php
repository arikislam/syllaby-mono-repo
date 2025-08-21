<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Speeches\Speech;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Speech */
class SpeechResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'voice_id' => $this->voice_id,
            'provider_id' => $this->provider_id,
            'provider' => $this->provider,
            'url' => $this->url,
            'status' => $this->status->value,
            'synced_at' => $this->synced_at?->toJSON(),
            'is_custom' => $this->is_custom,

            'media' => MediaResource::collection($this->whenLoaded('media')),

            'created_at' => $this->created_at->toJson(),
            'updated_at' => $this->updated_at->toJson(),
        ];
    }
}
