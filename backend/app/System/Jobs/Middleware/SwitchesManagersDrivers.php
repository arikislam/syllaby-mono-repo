<?php

namespace App\System\Jobs\Middleware;

use Closure;
use App\System\SwitchableDriver;
use Illuminate\Support\Facades\Cache;
use App\Syllaby\Generators\Exceptions\UnavailableAiAssistantDriver;

class SwitchesManagersDrivers
{
    private string $key;

    protected SwitchableDriver $manager;

    public function __construct(protected int $limit) {}

    /**
     * Process the queued job.
     *
     * @throws UnavailableAiAssistantDriver
     */
    public function handle(object $job, Closure $next): void
    {
        $drivers = Cache::get($this->getManager()->getDriverKey());

        if ($this->noAvailableDrivers($drivers)) {
            Cache::forget($this->getKey());
            $job->fail('There are no available drivers');

            return;
        }

        try {
            $next($job);
        } catch (UnavailableAiAssistantDriver $exception) {
            $driver = $this->getManager()->getCurrentDriver();
            $attempts = Cache::increment($this->getKey());

            if ($attempts >= $this->limit) {
                $this->switch($driver, $drivers);
                Cache::forget($this->getKey());
            }

            throw $exception;
        }
    }

    /**
     * Set the cache value key for the current driver being attempted.
     */
    public function by(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function using(SwitchableDriver $manager): self
    {
        $this->manager = $manager;

        return $this;
    }

    public function getManager(): SwitchableDriver
    {
        return $this->manager;
    }

    /**
     * Switch to the next available service driver.
     */
    private function switch(string $driver, array $drivers): void
    {
        $drivers[$driver] = false;
        Cache::put($this->getManager()->getDriverKey(), $drivers, now()->addHour());
    }

    /**
     * Checks if all drivers are unavailable.
     */
    private function noAvailableDrivers(?array $drivers): bool
    {
        if (blank($drivers)) {
            return false;
        }

        return array_reduce($drivers, fn ($carry, $item) => $carry && ($item === false), true);
    }

    /**
     * Get the cache value key for the current driver being attempted.
     */
    private function getKey(): string
    {
        return $this->key ?? 'driver-attempts';
    }
}
