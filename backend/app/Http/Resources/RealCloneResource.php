<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Syllaby\RealClones\RealClone;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Syllaby\RealClones\Enums\RealCloneStatus;

/** @mixin RealClone */
class RealCloneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'footage_id' => $this->footage_id,
            'voice_id' => $this->voice_id,
            'avatar_id' => $this->avatar_id,
            'background' => $this->background,
            'url' => $this->url,
            'provider_id' => $this->provider_id,
            'provider' => $this->provider,
            'status' => $this->status,
            'script' => $this->script,
            'is_ready' => $this->isReady(),

            'avatar' => AvatarResource::make($this->whenLoaded('avatar')),
            'voice' => VoiceResource::make($this->whenLoaded('voice')),
            'speech' => SpeechResource::make($this->whenLoaded('speech')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'generator' => GeneratorResource::make($this->whenLoaded('generator')),
            'footage' => FootageResource::make($this->whenLoaded('footage')),

            'synced_at' => $this->synced_at?->toJSON(),
            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }

    /**
     * Check whether the digital twin media is ready
     */
    private function isReady(): bool
    {
        $completed = $this->status === RealCloneStatus::COMPLETED;

        return blank($this->url) && filled($this->synced_at) && $completed;
    }
}
