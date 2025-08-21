<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Metadata\Caption;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Caption */
class CaptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'model_id' => $this->model_id,
            'model_type' => $this->model_type,
            'content' => $this->content,
            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }
}
