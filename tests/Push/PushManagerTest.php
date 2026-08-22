<?php

namespace Welfare\Notifications\Tests\Push;

use Welfare\Notifications\Push\Drivers\FirebaseDriver;
use Welfare\Notifications\Push\PushManager;
use Welfare\Notifications\Tests\TestCase;

class PushManagerTest extends TestCase
{
    public function test_defaults_to_firebase(): void
    {
        $manager = new PushManager($this->makeContainer([]));

        $this->assertInstanceOf(FirebaseDriver::class, $manager->driver());
    }

    public function test_unknown_driver_throws(): void
    {
        $manager = new PushManager($this->makeContainer(['welfare-notifications' => ['push' => ['driver' => 'onesignal']]]));

        $this->expectException(\InvalidArgumentException::class);
        $manager->driver();
    }
}
