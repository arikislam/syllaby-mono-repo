<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Syllaby\Publisher\Publications\AccountPublication;

/** @property AccountPublication $pivot */
class AccountPublicationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'status' => $this->pivot->status,
            'error' => $this->pivot->error_message,
            'metadata' => $this->pivot->metadata,
            'post_type' => $this->pivot->post_type->value,
            'channels' => SocialChannelResource::make($this)
        ];
    }
}
