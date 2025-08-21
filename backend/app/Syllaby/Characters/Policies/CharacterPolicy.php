<?php

namespace App\Syllaby\Characters\Policies;

use Arr;
use App\Syllaby\Users\User;
use App\Http\Responses\ErrorCode;
use Illuminate\Auth\Access\Response;
use App\Syllaby\Characters\Character;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Syllaby\Characters\Enums\CharacterStatus;

class CharacterPolicy
{
    use HandlesAuthorization;

    public function show(User $user, Character $character): Response
    {
        if (is_null($character->user_id)) {
            return $this->allow();
        }

        if ($user->owns($character)) {
            return $this->allow();
        }

        return $this->deny('You do not own this character.');
    }

    public function train(User $user, Character $character): Response
    {
        $cost = Arr::get(config('credit-engine.events'), sprintf('%s.min_amount', CreditEventEnum::CUSTOM_CHARACTER_PURCHASED->value));

        if (! $user->owns($character)) {
            return $this->deny('You do not own this character.');
        }

        if (! $this->hasEnoughCredits($user, $cost)) {
            return $this->deny('You do not have enough credits to train this character.', ErrorCode::INSUFFICIENT_CREDITS->value);
        }

        if ($character->status->inProgress()) {
            return $this->deny('Character is already being trained.');
        }

        if (! $character->status->is(CharacterStatus::PREVIEW_READY)) {
            return $this->deny('Character is not ready for training. Please ensure the preview is generated successfully.');
        }

        return $this->allow();
    }

    public function update(User $user, Character $character): Response
    {
        if (! $user->owns($character)) {
            return $this->deny('You do not own this character.');
        }

        if ($character->status->is(CharacterStatus::READY)) {
            return $this->deny('Character is ready and cannot be updated. Please create a new character instead');
        }

        if ($character->status->inProgress()) {
            return $this->deny('Character is already being trained.');
        }

        return $this->allow();
    }

    public function destroy(User $user, Character $character): Response
    {
        if (! $user->owns($character)) {
            return $this->deny('You do not own this character.');
        }

        if ($character->status->inProgress()) {
            return $this->deny('Character is currently being trained and cannot be deleted.');
        }

        return $this->allow();
    }

    private function hasEnoughCredits(User $user, int $cost): bool
    {
        $credits = $user->remaining_credit_amount + $user->extra_credits;

        return $credits >= $cost;
    }
}
