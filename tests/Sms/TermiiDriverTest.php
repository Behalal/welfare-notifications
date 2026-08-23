<?php

namespace Welfare\Notifications\Tests\Sms;

use Illuminate\Http\Client\Factory as HttpFactory;
use Welfare\Notifications\Sms\Drivers\TermiiDriver;
use Welfare\Notifications\Tests\TestCase;

class TermiiDriverTest extends TestCase
{
    public function test_successful_send_returns_success_result(): void
    {
        $http = new HttpFactory();
        $http->fake(['*/api/sms/send' => $http::response(['code' => 'ok', 'message_id' => 'abc', 'balance' => 150.5], 200)]);

        $driver = new TermiiDriver($http, 'test-key', 'WelfareBank', 'https://api.ng.termii.com');
        $result = $driver->send('08012345678', 'Your account was credited ₦5,000.00');

        $this->assertTrue($result->successful);
        $this->assertSame('termii', $result->provider);
        $this->assertSame(150.5, $result->meta['balance']);
    }

    public function test_phone_number_is_sent_digits_only_without_leading_plus(): void
    {
        // Termii's documented format is "2348012345678", not the standard
        // E.164 "+2348012345678" -- a leading "+" gets a 400 field-validation
        // rejection in production (confirmed against live Termii).
        $http = new HttpFactory();
        $http->fake(['*/api/sms/send' => $http::response(['code' => 'ok'], 200)]);

        $driver = new TermiiDriver($http, 'test-key', 'WelfareBank', 'https://api.ng.termii.com');
        $driver->send('08012345678', 'Test');

        $http->assertSent(function ($request) {
            return $request['to'] === '2348012345678';
        });
    }

    public function test_provider_rejection_returns_failure_result(): void
    {
        $http = new HttpFactory();
        $http->fake(['*/api/sms/send' => $http::response(['code' => 'error', 'message' => 'Insufficient balance'], 200)]);

        $driver = new TermiiDriver($http, 'test-key', 'WelfareBank', 'https://api.ng.termii.com');
        $result = $driver->send('08012345678', 'Test');

        $this->assertFalse($result->successful);
        $this->assertSame('Insufficient balance', $result->message);
    }

    public function test_network_failure_is_caught_and_never_throws(): void
    {
        $http = new HttpFactory();
        $http->fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $driver = new TermiiDriver($http, 'test-key', 'WelfareBank', 'https://api.ng.termii.com');
        $result = $driver->send('08012345678', 'Test');

        $this->assertFalse($result->successful);
    }

    public function test_api_key_is_never_exposed_in_the_result_message(): void
    {
        $http = new HttpFactory();
        $http->fake(['*/api/sms/send' => $http::response(['code' => 'error', 'message' => 'Bad request'], 400)]);

        $driver = new TermiiDriver($http, 'super-secret-key', 'WelfareBank', 'https://api.ng.termii.com');
        $result = $driver->send('08012345678', 'Test');

        $this->assertStringNotContainsString('super-secret-key', (string) $result->message);
    }
}
