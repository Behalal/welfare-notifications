<?php

namespace Welfare\Notifications\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void send(\Welfare\Notifications\Recipient $recipient, \Welfare\Notifications\NotificationPayload $payload, bool $force = false)
 *
 * @see \Welfare\Notifications\NotificationDispatcher
 */
class Notify extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Welfare\Notifications\NotificationDispatcher::class;
    }
}
