<?php

namespace App\Syllaby\Generators\Policies;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Videos\Faceless;
use Illuminate\Auth\Access\Response;
use App\Http\Responses\ErrorCode as Code;
use App\Syllaby\Credits\Enums\CreditEventEnum;

class GeneratorPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function generate(User $user, CreditEventEnum $action, int $amount = 1): Response
    {
        $events = config('credit-engine.events');
        $cost = Arr::get($events, "{$action->value}.min_amount", 6) * $amount;

        if (! $this->hasEnoughCredits($user, $cost)) {
            return Response::deny('Not enough credits to make the request', Code::INSUFFICIENT_CREDITS->value);
        }

        return Response::allow();
    }

    /**
     * Determine if the user has the credits to generate the faceless video.
     */
    public function faceless(User $user, Faceless $faceless, ?int $voiceId): Response
    {
        $events = config('credit-engine.events');
        $type = CreditEventEnum::FACELESS_VIDEO_GENERATED;
        $unit = Arr::get($events, "{$type->value}.min_amount");

        if ($faceless->length === 'short') {
            return $this->respond($user, $unit);
        }

        if (! $voice = Voice::find($voiceId)) {
            return Response::deny();
        }

        $duration = reading_time($faceless->script, $voice->words_per_minute);
        $cost = ceil($duration / 60) * $unit;

        return $this->respond($user, $cost);
    }

    /**
     * Checks whether the authenticated user has credit to perform the operation.
     */
    private function hasEnoughCredits(User $user, int $cost): bool
    {
        return $user->credits() >= $cost;
    }

    /**
     * Resolves the appropriate response.
     */
    private function respond(User $user, int $cost): Response
    {
        if (! $this->hasEnoughCredits($user, $cost)) {
            return Response::deny("You need at least {$cost} credits for this action", Code::INSUFFICIENT_CREDITS->value);
        }

        return Response::allow();
    }
}
