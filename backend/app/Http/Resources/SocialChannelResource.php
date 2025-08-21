<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

/**
 * @mixin SocialChannel
 *
 * @property int $views_count
 * @property int $likes_count
 * @property int $comments_count
 */
class SocialChannelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->account->provider->name,
            'provider_id' => $this->provider_id,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'type' => $this->type,
            'icon' => $this->account->icon,
            'needs_reauth' => $this->account->needs_reauth,
            'expires_in' => $this->resolveExpiresIn(),
            'errors' => $this->account->errors,
            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }

    private function resolveExpiresIn(): int
    {
        //        if ($this->account->expires_in === 0) {
        //            return 0;
        //        }
        //
        //        return match ($this->account->provider) {
        //            SocialAccountEnum::TikTok, SocialAccountEnum::Youtube => 31_536_000, // 365 days in seconds
        //            SocialAccountEnum::Facebook, SocialAccountEnum::Instagram, SocialAccountEnum::Threads => 5_184_000, // 60 days in seconds
        //            default => 0,
        //        };
        return 94_608_000;
    }
}
