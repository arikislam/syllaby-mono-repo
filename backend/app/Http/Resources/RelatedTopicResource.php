<?php

namespace App\Http\Resources;

use App\Syllaby\Ideas\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Topic */
class RelatedTopicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'language' => $this->language,
            'type' => $this->type,
            'ideas' => $this->ideas,
            'is_bookmarked' => (bool) $this->is_bookmarked,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
