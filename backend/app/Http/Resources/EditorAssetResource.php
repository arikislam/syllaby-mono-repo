<?php

namespace App\Http\Resources;

use App\Syllaby\Editor\EditorAsset;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EditorAsset */
class EditorAssetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'preview' => $this->preview,
            'key' => $this->key,
            'value' => $this->value,
            'active' => $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
