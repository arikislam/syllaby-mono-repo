<?php

namespace App\Http\Controllers\Api\v1\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\NotificationSettingsRequest;

class NotificationSettingsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function update(NotificationSettingsRequest $request)
    {
        $user = $this->user();

        $notifications = $user->preferences('notifications')->apply($request->validated());

        if (!$user->update(compact('notifications'))) {
            return $this->errorInternalError('Whoops! Something went wrong.');
        }

        return $this->respondWithMessage('Notifications preferences updated.');
    }
}
