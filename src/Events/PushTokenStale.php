<?php

namespace Welfare\Notifications\Events;

/**
 * Fired when Firebase reports a push token as no longer registered. The
 * package has no user table of its own to update — each host app listens
 * for this in its own EventServiceProvider and clears the token from
 * whatever model owns it, e.g.:
 *
 *   Event::listen(PushTokenStale::class, fn ($e) =>
 *       User::where('fcm_token', $e->token)->update(['fcm_token' => null])
 *   );
 */
final class PushTokenStale
{
    public function __construct(
        public readonly string $token,
    ) {
    }
}
