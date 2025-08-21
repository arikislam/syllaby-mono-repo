<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Syllaby\Publisher\Channels\SocialAccount;

/** @mixin SocialAccount */
class SocialAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider->name,
            'needs_re_auth' => $this->needs_reauth,
            'icon' => $this->icon,
            'errors' => $this->errors,
            'expires_in' => $this->expires_in,

            'channels' => SocialChannelResource::collection($this->whenLoaded('channels')),
        ];
    }
}
