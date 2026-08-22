<?php

namespace Welfare\Notifications\Tests\Sms;

use Welfare\Notifications\Sms\Drivers\AfricasTalkingDriver;
use Welfare\Notifications\Sms\Drivers\TermiiDriver;
use Welfare\Notifications\Sms\Drivers\TwilioDriver;
use Welfare\Notifications\Sms\SmsManager;
use Welfare\Notifications\Tests\TestCase;

class SmsManagerTest extends TestCase
{
    public function test_defaults_to_termii_when_no_driver_configured(): void
    {
        $manager = new SmsManager($this->makeContainer([]));

        $this->assertInstanceOf(TermiiDriver::class, $manager->driver());
    }

    public function test_resolves_termii_driver(): void
    {
        $manager = new SmsManager($this->makeContainer(['welfare-notifications' => ['sms' => ['driver' => 'termii']]]));

        $this->assertInstanceOf(TermiiDriver::class, $manager->driver());
    }

    public function test_resolves_africastalking_driver(): void
    {
        $manager = new SmsManager($this->makeContainer(['welfare-notifications' => ['sms' => ['driver' => 'africastalking']]]));

        $this->assertInstanceOf(AfricasTalkingDriver::class, $manager->driver());
    }

    public function test_resolves_twilio_driver(): void
    {
        $manager = new SmsManager($this->makeContainer(['welfare-notifications' => ['sms' => ['driver' => 'twilio']]]));

        $this->assertInstanceOf(TwilioDriver::class, $manager->driver());
    }

    public function test_unknown_driver_throws(): void
    {
        $manager = new SmsManager($this->makeContainer(['welfare-notifications' => ['sms' => ['driver' => 'carrier-pigeon']]]));

        $this->expectException(\InvalidArgumentException::class);
        $manager->driver();
    }

    public function test_switching_driver_via_config_requires_no_call_site_changes(): void
    {
        // Simulates SMS_PROVIDER changing between deployments — the same
        // ->driver() call resolves a different concrete class with zero
        // code change, which is the entire point of the Manager pattern.
        $termii = new SmsManager($this->makeContainer(['welfare-notifications' => ['sms' => ['driver' => 'termii']]]));
        $twilio = new SmsManager($this->makeContainer(['welfare-notifications' => ['sms' => ['driver' => 'twilio']]]));

        $this->assertNotSame(get_class($termii->driver()), get_class($twilio->driver()));
    }
}
