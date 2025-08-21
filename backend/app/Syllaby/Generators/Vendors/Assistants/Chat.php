<?php

namespace App\Syllaby\Generators\Vendors\Assistants;

use Illuminate\Support\Manager;
use Tests\Stubs\StubChatManager;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use App\Syllaby\Generators\DTOs\ChatConfig;

/**
 * @method send(string $message, ?ChatConfig $config = null)
 */
class Chat extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return ChatManager::class;
    }

    public static function fake(): void
    {
        static::swap(static::createFakeDriver());
    }

    protected static function createFakeDriver(): Manager
    {
        return new StubChatManager(Container::getInstance());
    }
}
