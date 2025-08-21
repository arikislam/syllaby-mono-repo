<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Syllaby\Loggers\Suppression;
use Illuminate\Support\Facades\Http;

class SESWebhookController extends Controller
{
    /**
     * Handle the SES webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        $notification = json_decode($request->getContent(), true);

        if (! Arr::has($notification, 'Type')) {
            return response()->json(['message' => 'Invalid SES Notification'], 400);
        }

        if (Arr::get($notification, 'Type') === 'SubscriptionConfirmation') {
            return $this->confirm($notification);
        }

        return match (Arr::get($notification, 'Type')) {
            'Notification' => $this->handleNotification($notification),
            default => response()->json(['message' => 'Processed']),
        };
    }

    /**
     * Handle SNS Subscription Confirmation.
     */
    private function confirm(array $notification): JsonResponse
    {
        if (! $url = Arr::get($notification, 'SubscribeURL')) {
            return response()->json(['message' => 'No SubscribeURL found'], 400);
        }

        if (Http::get($url)->failed()) {
            return response()->json(['message' => 'Subscription confirmation failed'], 500);
        }

        return response()->json(['message' => 'Subscription confirmed']);
    }

    /**
     * Handle a notification.
     */
    private function handleNotification(array $notification): JsonResponse
    {
        $message = json_decode(Arr::get($notification, 'Message'), true);

        if (! Arr::has($message, 'eventType')) {
            return response()->json(['message' => 'Invalid SES Notification'], 400);
        }

        match (Arr::get($message, 'eventType')) {
            'Bounce' => $this->handleBounce($message),
            'Complaint' => $this->handleComplaint($message),
            default => null,
        };

        return response()->json(['message' => 'Processed']);
    }

    /**
     * Handle a bounce notification.
     */
    private function handleBounce(array $message): void
    {
        $type = Arr::get($message, 'bounce.bounceType');
        $recipients = Arr::get($message, 'bounce.bouncedRecipients');

        foreach ($recipients as $recipient) {
            $email = Arr::get($recipient, 'emailAddress');

            $suppression = Suppression::updateOrCreate(['email' => $email], [
                'bounce_type' => $type,
                'bounced_at' => now(),
                'reason' => Arr::get($recipient, 'diagnosticCode'),
                'trace_id' => Arr::get($message, 'bounce.feedbackId'),
            ]);

            if ($type === 'Permanent') {
                $suppression->update(['soft_bounce_count' => 0]);
            } else {
                $suppression->increment('soft_bounce_count');
            }
        }
    }

    /**
     * Handle a complaint notification.
     */
    private function handleComplaint(array $notification): void
    {
        $recipients = Arr::get($notification, 'complaint.complainedRecipients');

        foreach ($recipients as $recipient) {
            $email = Arr::get($recipient, 'emailAddress');

            Suppression::updateOrCreate(['email' => $email], [
                'complained_at' => now(),
                'trace_id' => Arr::get($notification, 'complaint.feedbackId'),
            ]);
        }
    }
}
