<?php

namespace App\Syllaby\Users\Preferences;

trait HasPreferences
{
    /**
     * User preferences.
     */
    public function preferences(string $type = 'settings'): Preferences
    {
        return match ($type) {
            'settings' => new Settings($this->settings),
            'notifications' => new Notifications($this->notifications),
            default => null
        };
    }

    /**
     * Gets the user notifiable channels for the given notification type
     */
    public function channelsFor(string $type): array
    {
        $notifications = $this->preferences('notifications');

        return array_keys(array_filter($notifications->get($type)));
    }
}
