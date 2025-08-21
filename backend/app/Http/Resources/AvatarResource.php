<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\RealClones\Avatar;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Syllaby\RealClones\Enums\RealCloneProvider;

/** @mixin Avatar */
class AvatarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'gender' => $this->gender,
            'preview' => $this->preview_url,
            'provider' => $this->provider,
            'provider_id' => $this->provider_id,
            'label' => $this->resolveLabel(),
            'type' => $this->type,
            'is_active' => $this->is_active,
            'metadata' => $this->metadata,
        ];
    }

    private function resolveLabel(): string
    {
        return match ($this->provider->value) {
            RealCloneProvider::D_ID->value => '1.0',
            RealCloneProvider::HEYGEN->value => '2.0',
            default => '0.0',
        };
    }
}
