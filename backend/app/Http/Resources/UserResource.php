<?php

namespace App\Http\Resources;

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\RealClones\Avatar;
use App\Syllaby\Clonables\Clonable;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        $subscription = $this->subscription();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'stripe_id' => $this->stripe_id,
            'email' => $this->email,

            'credits' => [
                'remaining' => (int) $this->remaining_credit_amount,
                'total' => (int) $this->monthly_credit_amount,
                'extra' => $this->extra_credits,
            ],

            'subscription' => [
                'exists' => filled($subscription),
                'trial' => $subscription?->onTrial(),
                'active' => $subscription?->recurring(),
            ],

            'settings' => $this->settings,
            'notifications' => $this->notifications,
            'features' => $this->mergeFeatures(),
            'source' => $this->source,
            'hide_real_clone' => ! ($this->hasRealClonePurchase() || $this->hasAvatarVideo()),

            'answers' => SurveyAnswerResource::collection($this->whenLoaded('answers')),
            'show_welcome_modal' => $this->show_welcome_modal,
            'created_at' => $this->created_at->toJson(),
            'updated_at' => $this->updated_at->toJson(),

        ];
    }

    private function mergeFeatures(): array
    {
        return filled($this->plan_id) ? Feature::all() : [];
    }

    private function hasRealClonePurchase(): bool
    {
        return Clonable::where('user_id', $this->id)
            ->where('model_type', 'avatar')
            ->exists();
    }

    private function hasAvatarVideo(): bool
    {
        return Avatar::where('user_id', $this->id)
            ->whereIn('type', [Avatar::REAL_CLONE, Avatar::REAL_CLONE_LITE])
            ->exists();
    }
}
