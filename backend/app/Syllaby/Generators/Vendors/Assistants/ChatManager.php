<?php

namespace App\Syllaby\Generators\Vendors\Assistants;

use Override;
use Illuminate\Support\Arr;
use Illuminate\Support\Manager;
use App\System\SwitchableDriver;
use App\System\Traits\HandlesManagersDrivers;
use App\Syllaby\Generators\Contracts\ChatContract;

class ChatManager extends Manager implements SwitchableDriver
{
    use HandlesManagersDrivers;

    /**
     * Instantiates the ChapGPT driver.
     */
    public function createGptDriver(): ChatContract
    {
        return new GPT;
    }

    /**
     * Instantiates the Claude driver.
     */
    public function createClaudeDriver(): ChatContract
    {
        return new Claude;
    }

    /**
     * Instantiates the Claude driver.
     */
    public function createXAiDriver(): ChatContract
    {
        return new XAi;
    }

    /**
     * Instantiates the Claude driver.
     */
    public function createGeminiDriver(): ChatContract
    {
        return new Gemini;
    }

    /**
     * Get the default chat driver name.
     */
    public function getDefaultDriver()
    {
        return config('services.assistant.default');
    }

    /**
     * Cache identifier for the current chat driver.
     */
    public function getDriverKey(): string
    {
        return 'chat-driver';
    }

    /**
     * Get the configuration for all available drivers.
     */
    public function getAvailableDrivers(): array
    {
        $drivers = config('services.assistant.drivers', []);

        return Arr::mapWithKeys($drivers, fn ($driver) => [$driver => true]);
    }

    #[Override]
    protected function createDriver($driver): mixed
    {
        $this->driver = $this->switch($driver);

        return parent::createDriver($this->driver);
    }
}
