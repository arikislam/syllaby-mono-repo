<?php

namespace App\System\Traits;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Support\Facades\Cache;

trait HandlesManagersDrivers
{
    private ?string $driver;

    /**
     * Get the current used driver name.
     */
    public function getCurrentDriver(): string
    {
        return $this->driver;
    }

    private function switch($driver): string
    {
        if (Cache::missing($this->getDriverKey())) {
            Cache::put($this->getDriverKey(), $this->getAvailableDrivers(), now()->addHour());
        }

        if (! $driver = $this->preferred($driver)) {
            throw new InvalidArgumentException('No drivers available');
        }

        return $driver;
    }

    /**
     * Validate and return the driver to use.
     */
    private function preferred(string $driver): ?string
    {
        $drivers = Cache::get($this->getDriverKey());

        if (Arr::get($drivers, $driver, false)) {
            return $driver;
        }

        return Arr::first(array_keys($drivers), fn ($key) => $drivers[$key] === true);
    }
}
