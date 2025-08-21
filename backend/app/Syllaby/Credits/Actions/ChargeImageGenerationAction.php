<?php

namespace App\Syllaby\Credits\Actions;

use App\Syllaby\Users\User;
use Illuminate\Database\Eloquent\Model;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;

class ChargeImageGenerationAction
{
    /**
     * Charges the user after the threshold has been achieved.
     */
    public function handle(User $user, ?Model $creditable = null, int $count = 1, ?string $label = null): void
    {
        (new CreditService($user))->decrement(
            type: CreditEventEnum::SINGLE_AI_IMAGE_GENERATED,
            creditable: $creditable,
            amount: $count,
            label: $label ?? 'Image Generation'
        );
    }
}
