<?php

namespace Welfare\Notifications;

use Illuminate\Support\ServiceProvider;
use Welfare\Notifications\Push\PushManager;
use Welfare\Notifications\Sms\SmsManager;

class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/notifications.php', 'notifications');

        $this->app->singleton(SmsManager::class);
        $this->app->singleton(PushManager::class);
        $this->app->singleton(NotificationDispatcher::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/notifications.php' => $this->app->configPath('notifications.php'),
        ], 'notifications-config');
    }
}
