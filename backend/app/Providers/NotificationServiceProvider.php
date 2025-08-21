<?php

namespace App\Providers;

use App\System\MailChannel;
use Illuminate\Mail\Markdown;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Notification::extend('mail', function ($app) {
            return new MailChannel($app->make('mail.manager'), $app->make(Markdown::class));
        });
    }
}
