<?php

namespace App\Syllaby\Subscriptions\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Subscriptions\GooglePlayRtdn;
use App\Syllaby\Subscriptions\GooglePlaySubscription;

class GooglePlaySubscriptionLifecycleService
{
    /**
     * Get the complete lifecycle of a subscription by purchase token.
     */
    public function getLifecycle(string $purchaseToken): Collection
    {
        return GooglePlayRtdn::forPurchaseToken($purchaseToken)
            ->lifecycleOrdered()
            ->get();
    }

    /**
     * Get the current status of a subscription based on its lifecycle.
     */
    public function getCurrentStatus(string $purchaseToken): array
    {
        $lifecycle = $this->getLifecycle($purchaseToken);

        if ($lifecycle->isEmpty()) {
            return [
                'status' => 'unknown',
                'last_event' => null,
                'last_event_date' => null,
                'is_active' => false,
                'renewal_count' => 0,
            ];
        }

        $latestEvent = $lifecycle->last();
        $renewalCount = $lifecycle->where('notification_type', 'subscription.renewed')->count();

        $isActive = $this->determineIfActive($lifecycle);

        return [
            'status' => $this->mapNotificationToStatus($latestEvent->notification_type),
            'last_event' => $latestEvent->notification_type,
            'last_event_date' => $latestEvent->created_at,
            'is_active' => $isActive,
            'renewal_count' => $renewalCount,
            'lifecycle_events' => $lifecycle->count(),
        ];
    }

    /**
     * Get subscription statistics for a purchase token.
     */
    public function getSubscriptionStats(string $purchaseToken): array
    {
        $lifecycle = $this->getLifecycle($purchaseToken);

        if ($lifecycle->isEmpty()) {
            return [
                'total_events' => 0,
                'renewals' => 0,
                'cancellations' => 0,
                'first_purchase_date' => null,
                'last_activity_date' => null,
                'subscription_duration_days' => 0,
            ];
        }

        $firstEvent = $lifecycle->first();
        $lastEvent = $lifecycle->last();

        $renewals = $lifecycle->where('notification_type', 'subscription.renewed')->count();
        $cancellations = $lifecycle->whereIn('notification_type', [
            'subscription.canceled',
            'subscription.expired',
            'subscription.revoked',
        ])->count();

        $durationDays = $firstEvent->created_at->diffInDays($lastEvent->created_at);

        return [
            'total_events' => $lifecycle->count(),
            'renewals' => $renewals,
            'cancellations' => $cancellations,
            'first_purchase_date' => $firstEvent->created_at,
            'last_activity_date' => $lastEvent->created_at,
            'subscription_duration_days' => $durationDays,
        ];
    }

    /**
     * Check if a subscription should be considered active based on its lifecycle.
     */
    public function isSubscriptionActive(string $purchaseToken): bool
    {
        $lifecycle = $this->getLifecycle($purchaseToken);

        return $this->determineIfActive($lifecycle);
    }

    /**
     * Get all renewal events for a subscription.
     */
    public function getRenewals(string $purchaseToken): Collection
    {
        return GooglePlayRtdn::forPurchaseToken($purchaseToken)
            ->where('notification_type', 'subscription.renewed')
            ->lifecycleOrdered()
            ->get();
    }

    /**
     * Get the cancellation event for a subscription if it exists.
     */
    public function getCancellation(string $purchaseToken): ?GooglePlayRtdn
    {
        return GooglePlayRtdn::forPurchaseToken($purchaseToken)
            ->whereIn('notification_type', [
                'subscription.canceled',
                'subscription.expired',
                'subscription.revoked',
            ])
            ->latest('created_at')
            ->first();
    }

    /**
     * Sync RTDN lifecycle data with GooglePlaySubscription record.
     */
    public function syncWithSubscriptionRecord(string $purchaseToken): void
    {
        $subscription = GooglePlaySubscription::where('purchase_token', $purchaseToken)->first();

        if (! $subscription) {
            Log::warning('Cannot sync lifecycle - subscription record not found', [
                'purchase_token' => $purchaseToken,
            ]);

            return;
        }

        $currentStatus = $this->getCurrentStatus($purchaseToken);
        $stats = $this->getSubscriptionStats($purchaseToken);

        // Update subscription metadata with lifecycle information
        $metadata = $subscription->metadata ?? [];
        $metadata['lifecycle'] = [
            'total_events' => $stats['total_events'],
            'renewal_count' => $stats['renewals'],
            'last_rtdn_sync' => now()->toISOString(),
            'current_status' => $currentStatus['status'],
            'is_active_per_lifecycle' => $currentStatus['is_active'],
        ];

        $subscription->update(['metadata' => $metadata]);

        Log::info('Subscription lifecycle synced', [
            'subscription_id' => $subscription->id,
            'purchase_token' => $purchaseToken,
            'lifecycle_events' => $stats['total_events'],
            'renewals' => $stats['renewals'],
        ]);
    }

    /**
     * Get a timeline view of subscription events.
     */
    public function getTimeline(string $purchaseToken): array
    {
        $lifecycle = $this->getLifecycle($purchaseToken);

        return $lifecycle->map(function ($rtdn) {
            return [
                'id' => $rtdn->id,
                'event' => $rtdn->notification_type,
                'event_label' => $rtdn->getNotificationTypeLabel(),
                'status' => $rtdn->getStatusLabel(),
                'date' => $rtdn->created_at->toISOString(),
                'processed' => $rtdn->isProcessed(),
                'verified' => $rtdn->google_api_verified,
                'message_id' => $rtdn->message_id,
            ];
        })->toArray();
    }

    /**
     * Determine if subscription is active based on lifecycle events.
     */
    private function determineIfActive(Collection $lifecycle): bool
    {
        if ($lifecycle->isEmpty()) {
            return false;
        }

        $latestEvent = $lifecycle->last();

        // Check if latest event indicates cancellation
        $cancellationEvents = [
            'subscription.canceled',
            'subscription.expired',
            'subscription.revoked',
        ];

        if (in_array($latestEvent->notification_type, $cancellationEvents)) {
            return false;
        }

        // Check if we have any purchase or renewal events
        $activeEvents = [
            'subscription.purchased',
            'subscription.renewed',
        ];

        return $lifecycle->whereIn('notification_type', $activeEvents)->isNotEmpty();
    }

    /**
     * Map notification type to human-readable status.
     */
    private function mapNotificationToStatus(string $notificationType): string
    {
        return match ($notificationType) {
            'subscription.purchased' => 'purchased',
            'subscription.renewed' => 'renewed',
            'subscription.canceled' => 'canceled',
            'subscription.expired' => 'expired',
            'subscription.revoked' => 'revoked',
            'subscription.paused' => 'paused',
            'subscription.resumed' => 'resumed',
            default => 'unknown',
        };
    }
}
