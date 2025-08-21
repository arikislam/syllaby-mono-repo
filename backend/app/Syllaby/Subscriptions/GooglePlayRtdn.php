<?php

namespace App\Syllaby\Subscriptions;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Syllaby\Subscriptions\Services\GooglePlayRtdnMapper;
use App\Syllaby\Subscriptions\Enums\GooglePlayVoidNotification;
use App\Syllaby\Subscriptions\Enums\GooglePlayProductNotification;
use App\Syllaby\Subscriptions\Enums\GooglePlaySubscriptionNotification;

/**
 * Google Play Real-Time Developer Notification Model
 *
 * Handles both subscription and one-time product notifications from Google Play.
 *
 * @property int $id
 * @property int $user_id
 * @property string $purchase_token
 * @property int $plan_id
 * @property string|null $message_id
 * @property string|null $notification_type
 * @property array|null $rtdn_response
 * @property Carbon|null $processed_at
 * @property int $status
 * @property array|null $processing_errors
 * @property bool $google_api_verified
 * @property array|null $google_api_response
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read Plan $plan
 */
class GooglePlayRtdn extends Model
{
    /**
     * Status constants
     */
    const int STATUS_PENDING = 0;

    const int STATUS_PROCESSED = 1;

    const int STATUS_FAILED = 2;

    const int STATUS_IGNORED = 3;

    /**
     * The table associated with the model.
     */
    protected $table = 'google_play_rtdn';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'purchase_token',
        'plan_id',
        'message_id',
        'notification_type',
        'rtdn_response',
        'processed_at',
        'status',
        'processing_errors',
        'google_api_verified',
        'google_api_response',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'rtdn_response' => 'array',
        'processing_errors' => 'array',
        'google_api_response' => 'array',
        'google_api_verified' => 'boolean',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan associated with the notification.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Scope a query to only include pending notifications.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include processed notifications.
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', self::STATUS_PROCESSED);
    }

    /**
     * Scope a query to only include failed notifications.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope a query to only include verified notifications.
     */
    public function scopeVerified($query)
    {
        return $query->where('google_api_verified', true);
    }

    /**
     * Scope a query to only include subscription notifications.
     */
    public function scopeSubscriptions($query)
    {
        return $query->where('notification_type', 'LIKE', 'subscription.%');
    }

    /**
     * Scope a query to only include one-time product notifications.
     */
    public function scopeOneTimeProducts($query)
    {
        return $query->whereIn('notification_type', GooglePlayProductNotification::getAllStrings());
    }

    /**
     * Scope a query to only include purchase notifications.
     */
    public function scopePurchases($query)
    {
        return $query->whereIn('notification_type', array_merge(
            GooglePlaySubscriptionNotification::getPurchaseStrings(),
            GooglePlayProductNotification::getPurchaseStrings()
        ));
    }

    /**
     * Scope a query to only include cancellation notifications.
     */
    public function scopeCancellations($query)
    {
        return $query->whereIn('notification_type', array_merge(
            GooglePlaySubscriptionNotification::getCancellationStrings(),
            GooglePlayProductNotification::getCancellationStrings(),
            GooglePlayVoidNotification::getCancellationStrings()
        ));
    }

    /**
     * Scope a query to only include voided purchase notifications.
     */
    public function scopeVoidedPurchases($query)
    {
        return $query->where('notification_type', GooglePlayVoidNotification::getNotificationType());
    }

    /**
     * Check if the notification is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the notification is processed.
     */
    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    /**
     * Check if the notification has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark the notification as processed.
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSED,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark the notification as failed with error details.
     */
    public function markAsFailed(array $errors = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'processed_at' => now(),
            'processing_errors' => $errors,
        ]);
    }

    /**
     * Mark the notification as verified with API response.
     */
    public function markAsVerified(array $apiResponse): void
    {
        $this->update([
            'google_api_verified' => true,
            'google_api_response' => $apiResponse,
        ]);
    }

    /**
     * Get the status label.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSED => 'Processed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_IGNORED => 'Ignored',
            default => 'Unknown',
        };
    }

    /**
     * Get a human-readable notification type.
     */
    public function getNotificationTypeLabel(): string
    {
        if (! $this->notification_type) {
            return 'Unknown';
        }

        return GooglePlayRtdnMapper::getNotificationLabel($this->notification_type);
    }

    /**
     * Check if the notification is for a subscription.
     */
    public function isSubscriptionNotification(): bool
    {
        return str_starts_with($this->notification_type ?? '', 'subscription.');
    }

    /**
     * Check if the notification is for a one-time product.
     */
    public function isOneTimeProductNotification(): bool
    {
        return in_array($this->notification_type, GooglePlayProductNotification::getAllStrings());
    }

    /**
     * Check if the notification is for a voided purchase.
     */
    public function isVoidedPurchaseNotification(): bool
    {
        return GooglePlayVoidNotification::isVoidNotification($this->notification_type ?? '');
    }

    /**
     * Check if the notification is for a purchase (new subscription or one-time product).
     */
    public function isPurchaseNotification(): bool
    {
        return GooglePlayRtdnMapper::isPurchaseEvent($this->notification_type ?? '');
    }

    /**
     * Check if the notification is for a cancellation/voided purchase.
     */
    public function isCancellationNotification(): bool
    {
        return GooglePlayRtdnMapper::isCancellationEvent($this->notification_type ?? '');
    }

    /**
     * Get the product type based on the notification type.
     */
    public function getProductType(): string
    {
        if ($this->isSubscriptionNotification()) {
            return 'subscription';
        }

        if ($this->isOneTimeProductNotification()) {
            return 'one_time';
        }

        if ($this->isVoidedPurchaseNotification()) {
            return 'voided';
        }

        return 'unknown';
    }

    /**
     * Get all RTDN records for the same purchase token (subscription lifecycle).
     */
    public function getSubscriptionLifecycle()
    {
        return static::where('purchase_token', $this->purchase_token)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get the latest RTDN record for this purchase token.
     */
    public function getLatestForPurchaseToken(): ?self
    {
        return static::where('purchase_token', $this->purchase_token)
            ->latest('created_at')
            ->first();
    }

    /**
     * Get the first RTDN record for this purchase token (initial purchase).
     */
    public function getInitialPurchaseRecord(): ?self
    {
        return static::where('purchase_token', $this->purchase_token)
            ->oldest('created_at')
            ->first();
    }

    /**
     * Check if this is the latest record for the purchase token.
     */
    public function isLatestForPurchaseToken(): bool
    {
        $latest = $this->getLatestForPurchaseToken();

        return $latest && $latest->id === $this->id;
    }

    /**
     * Get all renewal records for this purchase token.
     */
    public function getRenewalRecords()
    {
        return static::where('purchase_token', $this->purchase_token)
            ->where('notification_type', 'subscription.renewed')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get the cancellation record for this purchase token if it exists.
     */
    public function getCancellationRecord(): ?self
    {
        return static::where('purchase_token', $this->purchase_token)
            ->whereIn('notification_type', [
                'subscription.canceled',
                'subscription.expired',
                'subscription.revoked',
            ])
            ->latest('created_at')
            ->first();
    }

    /**
     * Check if the subscription has been canceled.
     */
    public function isSubscriptionCanceled(): bool
    {
        return $this->getCancellationRecord() !== null;
    }

    /**
     * Get subscription timeline with all events.
     */
    public function getSubscriptionTimeline(): array
    {
        $records = $this->getSubscriptionLifecycle();

        return $records->map(function ($record) {
            return [
                'id' => $record->id,
                'event' => $record->notification_type,
                'event_label' => $record->getNotificationTypeLabel(),
                'status' => $record->getStatusLabel(),
                'created_at' => $record->created_at,
                'processed_at' => $record->processed_at,
                'verified' => $record->google_api_verified,
            ];
        })->toArray();
    }

    /**
     * Scope to get records for a specific purchase token.
     */
    public function scopeForPurchaseToken($query, string $purchaseToken)
    {
        return $query->where('purchase_token', $purchaseToken);
    }

    /**
     * Scope to get the lifecycle of a subscription ordered by time.
     */
    public function scopeLifecycleOrdered($query)
    {
        return $query->orderBy('created_at');
    }
}
