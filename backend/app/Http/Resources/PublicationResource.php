<?php

namespace App\Http\Resources;

use App\Syllaby\Videos\Video;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @mixin Publication
 *
 * @property int $views_count
 * @property int $likes_count
 * @property int $comments_count
 */
class PublicationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'scheduled_at' => $this->whenLoaded('event', fn () => $this->event->starts_at?->toJSON()),
            'draft' => $this->draft,
            'temporary' => $this->temporary,
            'video_id' => $this->video_id,
            'script' => $this->getScript(),
            'media' => $this->resolveMediaResource(),
            'accounts' => AccountPublicationResource::collection($this->whenLoaded('channels')),
            'video' => VideoResource::make($this->whenLoaded('video')),
            'event' => EventResource::make($this->whenLoaded('event')),
            'aggregate' => [
                'views-count' => $this->views_count ?? 0,
                'likes-count' => $this->likes_count ?? 0,
                'comments-count' => $this->comments_count ?? 0,
            ],
            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }

    private function resolveMediaResource(): AnonymousResourceCollection
    {
        $items = self::collection([]);

        if ($this->relationLoaded('media') && $this->media->isNotEmpty()) {
            $items->push(...MediaResource::collection($this->media));
        }

        if ($this->relationLoaded('video') && $this->video && $this->video->relationLoaded('media') && $this->video->media->isNotEmpty()) {
            $items->push(...MediaResource::collection($this->video->media));
        }

        return $items;
    }

    private function getScript(): ?string
    {
        if (! $this->relationLoaded('video') || ! $this->video) {
            return null;
        }

        return match ($this->video->type) {
            Video::FACELESS => $this->video->faceless->script,
            Video::CUSTOM => $this->video->clones()->first()?->script,
            default => null,
        };
    }
}
