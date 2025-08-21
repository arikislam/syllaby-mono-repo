<?php

namespace App\Http\Resources;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Syllaby\Videos\Video;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Video */
class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'idea_id' => $this->idea_id,
            'scheduler_id' => $this->scheduler_id,
            'title' => $this->title,
            'provider' => $this->provider,
            'provider_id' => $this->provider_id,
            'type' => $this->type,
            'url' => $this->url,
            'status' => $this->status,
            'retries' => $this->retries,
            'hash' => $this->hash,
            'synced_at' => $this->synced_at?->toJSON(),

            'metadata' => [
                'ai_labels' => Arr::get($this->metadata, 'ai_labels', false),
                'custom_description' => Arr::get($this->metadata, 'custom_description'),
            ],

            'user' => UserResource::make($this->whenLoaded('user')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'idea' => IdeaResource::make($this->whenLoaded('idea')),
            'footage' => FootageResource::make($this->whenLoaded('footage')),
            'faceless' => FacelessResource::make($this->whenLoaded('faceless')),
            'resource' => FolderWithContentResource::make($this->whenLoaded('resource')),
            'publications' => PublicationResource::collection($this->whenLoaded('publications')),

            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }
}
