<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\Folders\Folder;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Folder
 */
class FolderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'color' => $this->color,
            'is_bookmarked' => (bool) $this->is_bookmarked,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
