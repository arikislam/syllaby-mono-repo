<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Folders\Resource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Resource
 */
class FolderWithContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'model_id' => $this->model_id,
            'model_type' => $this->model_type,
            'model' => $this->resolveModel(),
            'is_root' => $this->isRoot(),
            'parent' => $this->whenLoaded('parent', fn () => FolderWithContentResource::make($this->parent)),
            'children' => $this->whenLoaded('children', fn () => FolderWithContentResource::collection($this->children)),
            'ancestors' => $this->whenLoaded('ancestors', fn () => FolderWithContentResource::collection($this->ancestors)),
            'descendants' => $this->whenLoaded('descendants', fn () => FolderWithContentResource::collection($this->descendants)),
            'children_count' => $this->children_count ?? $this->folders_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function resolveModel(): mixed
    {
        return match ($this->model_type) {
            'folder' => $this->whenLoaded('model', fn () => FolderResource::make($this->model)),
            'video' => $this->whenLoaded('model', fn () => VideoResource::make($this->model)),
            default => null,
        };
    }
}
