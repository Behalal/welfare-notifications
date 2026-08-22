<?php

namespace Welfare\Notifications;

use Illuminate\Support\ServiceProvider;
use Welfare\Notifications\Push\PushManager;
use Welfare\Notifications\Sms\SmsManager;

class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Namespaced config key ('welfare-notifications', not the generic
        // 'notifications') — a host app may well already own a plain
        // config/notifications.php for unrelated settings, and Laravel's
        // mergeConfigFrom() only shallow-merges: an app-level 'sms' or
        // 'push' key would silently shadow this package's driver config
        // entirely. welfareMFB's own config/notifications.php was exactly
        // this collision, caught while wiring this package in.
        $this->mergeConfigFrom(__DIR__ . '/../config/welfare-notifications.php', 'welfare-notifications');

        $this->app->singleton(SmsManager::class);
        $this->app->singleton(PushManager::class);
        $this->app->singleton(NotificationDispatcher::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/welfare-notifications.php' => $this->app->configPath('welfare-notifications.php'),
        ], 'notifications-config');
    }
}
