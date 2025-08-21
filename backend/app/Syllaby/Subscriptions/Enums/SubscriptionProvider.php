<?php

namespace App\Syllaby\Subscriptions\Enums;

enum SubscriptionProvider: int
{
    case STRIPE = 1;
    case GOOGLE_PLAY = 2;
    case APPLE = 3;
    case JVZOO = 4;

    /**
     * Source platform constants
     */
    public const string SOURCE_WEB = 'web';

    public const string SOURCE_IOS = 'ios';

    public const string SOURCE_ANDROID = 'android';

    public const string SOURCE_JVZOO = 'jvzoo';

    /**
     * Get the human-readable label for the provider.
     */
    public function label(): string
    {
        return match ($this) {
            self::STRIPE => 'Stripe',
            self::GOOGLE_PLAY => 'Google Play',
            self::APPLE => 'Apple',
            self::JVZOO => 'JVZoo',
        };
    }

    /**
     * Get all valid source values.
     */
    public static function getSources(): array
    {
        return [
            self::SOURCE_WEB,
            self::SOURCE_IOS,
            self::SOURCE_ANDROID,
            self::SOURCE_JVZOO,
        ];
    }

    /**
     * Convert a subscription provider to its corresponding source
     */
    public function toSource(): string
    {
        return match ($this) {
            self::GOOGLE_PLAY => self::SOURCE_ANDROID,
            self::APPLE => self::SOURCE_IOS,
            self::STRIPE => self::SOURCE_WEB,
            self::JVZOO => self::SOURCE_JVZOO,
        };
    }

    /**
     * Get the subscription provider based on the source platform.
     */
    public static function fromSource(string $source): self
    {
        return match ($source) {
            self::SOURCE_ANDROID => self::GOOGLE_PLAY,
            self::SOURCE_IOS => self::APPLE,
            self::SOURCE_JVZOO => self::JVZOO,
            default => self::STRIPE,
        };
    }
}
