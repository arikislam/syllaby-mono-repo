<?php

namespace App\Syllaby\Surveys\Actions;

use App\Syllaby\Users\User;
use App\Syllaby\Surveys\UserFeedback;

class CreateUserFeedbackAction
{
    /**
     * Saves in storage the given user feedback.
     */
    public function handle(User $user, array $input): UserFeedback
    {
        return UserFeedback::create([
            'user_id' => $user->id,
            'reason' => data_get($input, 'reason'),
            'details' => data_get($input, 'details', null),
        ]);
    }
}
