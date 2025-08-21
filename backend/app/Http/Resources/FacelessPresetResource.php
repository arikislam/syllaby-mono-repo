<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Presets\FacelessPreset;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FacelessPreset */
class FacelessPresetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,

            'user_id' => $this->user_id,
            'user' => UserResource::make($this->whenLoaded('user')),

            'music_id' => $this->music_id,
            'music' => MediaResource::make($this->whenLoaded('music')),

            'music_category_id' => $this->music_category_id,
            'music_category' => TagResource::make($this->whenLoaded('music_category')),

            'voice_id' => $this->voice_id,
            'voice' => VoiceResource::make($this->whenLoaded('voice')),

            'background_id' => $this->background_id,
            'background' => AssetResource::make($this->whenLoaded('background')),

            'resource_id' => $this->resource_id,
            'resource' => FolderWithContentResource::make($this->whenLoaded('resource')),

            'genre_id' => $this->genre_id,
            'genre' => GenreResource::make($this->whenLoaded('genre')),

            'language' => $this->language,
            'font_family' => $this->font_family,
            'font_color' => $this->font_color,
            'position' => $this->position,
            'caption_animation' => $this->caption_animation,
            'duration' => (int) $this->duration,
            'orientation' => $this->orientation,
            'transition' => $this->transition,
            'volume' => $this->volume,
            'sfx' => $this->sfx,
            'overlay' => $this->overlay,

            'watermark_id' => $this->watermark_id,
            'watermark' => AssetResource::make($this->whenLoaded('watermark')),
            'watermark_position' => $this->watermark_position,
            'watermark_opacity' => $this->watermark_opacity,

            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }
}
