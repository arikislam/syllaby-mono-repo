<?php

namespace App\Http\Controllers\Api\v1\Notifications;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Http\Requests\Notifications\ReadNotificationRequest;
use App\Http\Requests\Notifications\IndexNotificationRequest;

class NotificationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Paginated display of authenticated user notifications.
     */
    public function index(IndexNotificationRequest $request): JsonResponse
    {
        $notifications = $this->user()->notifications();

        return $this->respondWithPagination(
            NotificationResource::collection(
                $notifications->paginate($this->take())
            )
        );
    }

    /**
     * Mark either all specific unread notifications as read.
     */
    public function update(ReadNotificationRequest $request): JsonResponse
    {
        $notifications = $this->user()->notifications()->unread();

        if ($ids = $request->input('notifications')) {
            $notifications = $notifications->whereIn('id', $ids);
        }

        $notifications->update(['read_at' => now()]);

        return $this->respondWithMessage('Notifications marked as read.');
    }
}
