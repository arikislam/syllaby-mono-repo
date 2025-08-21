<?php

namespace App\Http\Resources;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Syllaby\Assets\Media;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Media */
class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'file_name' => $this->file_name,
            'uuid' => $this->uuid,
            'collection' => $this->collection_name,
            'model_id' => $this->model_id,
            'model_type' => $this->model_type,
            'original_url' => $this->getFullUrl(),
            'download_url' => route('api.v1.download.media', $this->uuid),
            'order' => (int) $this->order_column,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'size' => $this->size,
            'conversions' => $this->formatConversions(),
            'custom_properties' => $this->formatProperties(),
            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }

    private function formatConversions(): ?array
    {
        if (blank($this->generated_conversions)) {
            return null;
        }

        return collect($this->generated_conversions)->mapWithKeys(fn ($item, $key) => [
            $key => $this->getFullUrl($key),
        ])->toArray();
    }

    private function formatProperties(): ?array
    {
        $filtered = Arr::except($this->custom_properties, 'custom_headers');

        return filled($filtered) ? $filtered : null;
    }
}
