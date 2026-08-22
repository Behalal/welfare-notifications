<?php

namespace Welfare\Notifications\Push;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Manager;
use Welfare\Notifications\Push\Drivers\FirebaseDriver;

/**
 * Resolves the active push provider from config('notifications.push.driver')
 * (backed by PUSH_PROVIDER in .env). Only one driver today (Firebase), but
 * kept behind the same Manager pattern as SMS so a second provider is a
 * drop-in addition later, not a redesign.
 *
 * @method \Welfare\Notifications\Push\Contracts\PushProvider driver(?string $driver = null)
 */
final class PushManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('welfare-notifications.push.driver', 'firebase');
    }

    protected function createFirebaseDriver(): FirebaseDriver
    {
        $config = $this->config->get('welfare-notifications.push.firebase', []);

        $events = $this->container->bound(EventDispatcher::class)
            ? $this->container->make(EventDispatcher::class)
            : null;

        return new FirebaseDriver($config['credentials'] ?? '', $events);
    }
}
