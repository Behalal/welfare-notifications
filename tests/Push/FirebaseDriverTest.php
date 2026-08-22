<?php

namespace Welfare\Notifications\Tests\Push;

use Welfare\Notifications\Push\Drivers\FirebaseDriver;
use Welfare\Notifications\Tests\TestCase;

/**
 * FirebaseDriver talks to the real Kreait SDK rather than an injectable
 * HTTP client, so a live send can't be unit-tested without real
 * credentials (that's what each host app's own integration test, run
 * against a real Firebase project, is for). What IS worth proving here
 * without credentials: the "never throw to the caller" contract every
 * driver must honour holds even when credentials are missing/invalid —
 * a misconfigured server should degrade to a logged failure, not a
 * fatal error that takes the queue worker down.
 */
class FirebaseDriverTest extends TestCase
{
    public function test_missing_credentials_file_returns_failure_result_instead_of_throwing(): void
    {
        $driver = new FirebaseDriver('/path/does/not/exist.json');

        $result = $driver->send('some-token', 'Title', 'Body');

        $this->assertFalse($result->successful);
        $this->assertSame('firebase', $result->provider);
    }
}
