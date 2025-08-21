<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Videos\Faceless;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Faceless */
class FacelessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'video_id' => $this->video_id,
            'voice_id' => $this->voice_id,
            'music_id' => $this->music_id,
            'background_id' => $this->background_id,
            'estimated_duration' => $this->estimated_duration,
            'length' => $this->length,
            'type' => $this->type,

            'genre' => GenreResource::make($this->whenLoaded('genre')),

            'script' => $this->script,
            'hash' => $this->hash,
            'options' => $this->options,
            'is_transcribed' => $this->is_transcribed,

            'watermark_id' => $this->watermark_id,
            'watermark' => AssetResource::make($this->whenLoaded('watermark')),

            'character_id' => $this->character_id,
            'character' => CharacterResource::make($this->whenLoaded('character')),

            'video' => VideoResource::make($this->whenLoaded('video')),
            'generator' => GeneratorResource::make($this->whenLoaded('generator')),
            'background' => AssetResource::make($this->whenLoaded('background')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'music' => MediaResource::make($this->whenLoaded('music')),
            'voice' => VoiceResource::make($this->whenLoaded('voice')),
            'trackers' => TrackerResource::collection($this->whenLoaded('trackers')),
            'assets' => AssetResource::collection($this->whenLoaded('assets')),
            'source' => TimelineResource::make($this->whenLoaded('timeline')),
            'captions' => CaptionResource::make($this->whenLoaded('captions')),

            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }
}
