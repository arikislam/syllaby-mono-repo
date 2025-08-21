<?php

namespace App\Syllaby\Subscriptions\Enums;

/**
 * Google Play Voided Purchase Notification
 *
 * Handles voided purchase notifications with references to separate
 * product type and refund type enums.
 *
 * @see https://developer.android.com/google/play/billing/rtdn-reference
 */
class GooglePlayVoidNotification
{
    /**
     * The notification type string for voided purchases
     */
    public const string NOTIFICATION_TYPE = 'purchase.voided';

    /**
     * Get the notification type string for database storage.
     */
    public static function getNotificationType(): string
    {
        return self::NOTIFICATION_TYPE;
    }

    /**
     * Get the human-readable label for voided purchase.
     */
    public static function getLabel(): string
    {
        return 'Purchase Voided';
    }

    /**
     * Check if a string represents a voided purchase notification.
     */
    public static function isVoidNotification(string $notificationType): bool
    {
        return $notificationType === self::NOTIFICATION_TYPE;
    }

    /**
     * Get voided purchase notification type as array for scope methods.
     */
    public static function getCancellationStrings(): array
    {
        return [self::NOTIFICATION_TYPE];
    }
}
