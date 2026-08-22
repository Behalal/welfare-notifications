<?php

namespace Welfare\Notifications\Sms;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Manager;
use Welfare\Notifications\Sms\Drivers\AfricasTalkingDriver;
use Welfare\Notifications\Sms\Drivers\TermiiDriver;
use Welfare\Notifications\Sms\Drivers\TwilioDriver;

/**
 * Resolves the active SMS provider from config('notifications.sms.driver')
 * (backed by SMS_PROVIDER in .env). Same pattern Laravel itself uses for
 * MAIL_MAILER / QUEUE_CONNECTION — adding a new provider is one new driver
 * class + one create*Driver() method here, nothing else in either host app
 * needs to change.
 *
 * @method \Welfare\Notifications\Sms\Contracts\SmsProvider driver(?string $driver = null)
 */
final class SmsManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('notifications.sms.driver', 'termii');
    }

    protected function createTermiiDriver(): TermiiDriver
    {
        $config = $this->config->get('notifications.sms.termii', []);

        return new TermiiDriver(
            $this->httpFactory(),
            $config['api_key'] ?? '',
            $config['sender_id'] ?? '',
            $config['endpoint'] ?? 'https://api.ng.termii.com',
            $config['channel'] ?? 'dnd',
        );
    }

    protected function createAfricastalkingDriver(): AfricasTalkingDriver
    {
        $config = $this->config->get('notifications.sms.africastalking', []);

        return new AfricasTalkingDriver(
            $this->httpFactory(),
            $config['api_key'] ?? '',
            $config['username'] ?? '',
            $config['sender_id'] ?? null,
            (bool) ($config['sandbox'] ?? false),
        );
    }

    protected function createTwilioDriver(): TwilioDriver
    {
        $config = $this->config->get('notifications.sms.twilio', []);

        return new TwilioDriver(
            $this->httpFactory(),
            $config['account_sid'] ?? '',
            $config['auth_token'] ?? '',
            $config['from_number'] ?? '',
        );
    }

    private function httpFactory(): HttpFactory
    {
        return $this->container->bound(HttpFactory::class)
            ? $this->container->make(HttpFactory::class)
            : new HttpFactory();
    }
}
