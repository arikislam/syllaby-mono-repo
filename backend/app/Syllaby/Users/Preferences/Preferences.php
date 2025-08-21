<?php

namespace App\Syllaby\Users\Preferences;

use Exception;
use Illuminate\Support\Arr;

abstract class Preferences
{
    /**
     * List of preferences.
     */
    protected array $preferences = [];

    /**
     * Create a new preferences instance.
     */
    public function __construct(array $preferences)
    {
        $this->preferences = $preferences;
    }

    /**
     * Retrieve a given preference.
     */
    public function get(string $key): mixed
    {
        return Arr::get($this->preferences, $key);
    }

    /**
     * Create and persist a new preference.
     */
    public function set(string $key, mixed $value): array
    {
        Arr::set($this->preferences, $key, $value);

        return $this->preferences;
    }

    /**
     * Determine if the given preference exists.
     */
    public function has(string $key): bool
    {
        return Arr::has($this->preferences, $key);
    }

    /**
     * Retrieve an array with all preferences.
     */
    public function all(): array
    {
        return $this->preferences;
    }

    /**
     * Merge the given attributes with the current settings.
     * But do not assign any new settings.
     */
    public function apply(array $attributes): array
    {
        foreach ($this->sanitize($attributes) as $key => $value) {
            $this->set($key, $value);
        }

        return $this->preferences;
    }

    /**
     * Protects against unwanted preferences.
     */
    private function sanitize(array $attributes): array
    {
        return Arr::only(
            Arr::dot($attributes),
            array_keys(Arr::dot($this->preferences))
        );
    }

    /**
     * Magic property access for preferences.
     *
     * @throws Exception
     */
    public function __get(string $key): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        throw new Exception("No preferences with $key key exists.");
    }
}
