<?php

namespace App\Syllaby\Subscriptions\Contracts;

interface GooglePlayServiceInterface
{
    /**
     * Sync an item to Google Play
     */
    public function sync(mixed $item, bool $force = false, bool $isTest = false, bool $isFake = false): array;

    /**
     * Sync all eligible items
     */
    public function syncAll(bool $isTest = false, bool $isFake = false): array;
}
