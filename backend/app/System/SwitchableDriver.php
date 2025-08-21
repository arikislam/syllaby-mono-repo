<?php

namespace App\System;

interface SwitchableDriver
{
    /**
     * Cache identifier for the current transcriber driver.
     */
    public function getDriverKey(): string;

    /**
     * Get the current used driver name.
     */
    public function getCurrentDriver(): string;

    /**
     * Get the configuration for all available drivers.
     */
    public function getAvailableDrivers(): array;
}
