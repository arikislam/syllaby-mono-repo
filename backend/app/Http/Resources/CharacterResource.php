<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Characters\Character;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Character */
class CharacterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'genre' => GenreResource::make($this->whenLoaded('genre')),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'training_images' => $this->training_images,
            'gender' => $this->gender,
            'status' => $this->status,
            'meta' => $this->meta,
            'active' => $this->active,

            $this->mergeWhen($this->isCustom(), [
                'reference' => MediaResource::make($this->getFirstMedia('reference')),
                'thumbnail' => MediaResource::make($this->getFirstMedia('preview')),
                'preview' => MediaResource::collection($this->getMedia('sandbox')),
            ], [
                'thumbnail' => Storage::disk('assets')->url("/faceless/characters/{$this->genre?->slug}/{$this->trigger}.webp"),
            ]),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
