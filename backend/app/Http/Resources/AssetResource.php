<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Assets\Asset;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Asset */
class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->whenPivotLoaded('video_assets', fn () => $this->pivot->uuid),
            'user_id' => $this->user_id,
            'parent_id' => $this->parent_id,
            'provider_id' => $this->provider_id,
            'genre_id' => $this->genre_id,
            'name' => $this->name,
            'type' => $this->type,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'is_private' => $this->is_private,
            'orientation' => $this->orientation,
            'order' => $this->whenPivotLoaded('video_assets', fn () => $this->pivot->order),
            'active' => $this->whenPivotLoaded('video_assets', fn () => $this->pivot->active),
            'is_bookmarked' => (bool) $this->is_bookmarked,
            'is_used' => (bool) $this->whenCounted('videos', fn () => $this->videos_count > 0),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'genre' => $this->whenLoaded('genre'),
            'user' => $this->whenLoaded('user'),
            'videos' => $this->whenLoaded('videos'),
            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }
}
