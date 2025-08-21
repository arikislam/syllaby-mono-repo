<?php

namespace App\Http\Resources;

use App\Syllaby\Speeches\Voice;
use App\Syllaby\Speeches\Vendors\Elevenlabs;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Voice */
class VoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'preview' => $this->preview_url,
            'language' => $this->language,
            'accent' => $this->accent,
            'gender' => $this->gender,
            'provider' => $this->provider,
            'provider_id' => $this->provider_id,
            'words_per_minute' => $this->words_per_minute ?? Elevenlabs::AVERAGE_WORDS_PER_MINUTE,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'metadata' => $this->metadata,
            'order' => $this->order,
            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }
}
