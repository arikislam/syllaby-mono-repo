<?php

namespace App\Http\Resources;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Syllaby\Characters\Genre;
use App\Syllaby\Videos\Enums\Dimension;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Genre */
class GenreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'active' => $this->active,
            'consistent_character' => $this->consistent_character,
            'has_prompt' => filled($this->prompt),
            'preview' => Arr::mapWithKeys(Dimension::values(), fn ($dimension) => [
                $dimension => Storage::disk('assets')->url("faceless/genres/{$this->slug}/{$dimension}.webp"),
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
