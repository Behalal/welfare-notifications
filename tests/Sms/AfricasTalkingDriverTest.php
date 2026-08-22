<?php

namespace Welfare\Notifications\Tests\Sms;

use Illuminate\Http\Client\Factory as HttpFactory;
use Welfare\Notifications\Sms\Drivers\AfricasTalkingDriver;
use Welfare\Notifications\Tests\TestCase;

class AfricasTalkingDriverTest extends TestCase
{
    public function test_successful_send_returns_success_result(): void
    {
        $http = new HttpFactory();
        $http->fake(['*africastalking.com/*' => $http::response([
            'SMSMessageData' => ['Recipients' => [['status' => 'Success']]],
        ], 200)]);

        $driver = new AfricasTalkingDriver($http, 'test-key', 'sandbox', 'WelfareBank');
        $result = $driver->send('08012345678', 'Test message');

        $this->assertTrue($result->successful);
        $this->assertSame('africastalking', $result->provider);
    }

    public function test_sandbox_flag_selects_sandbox_endpoint(): void
    {
        $http = new HttpFactory();
        $http->fake(['*' => $http::response(['SMSMessageData' => ['Recipients' => [['status' => 'Success']]]], 200)]);

        $driver = new AfricasTalkingDriver($http, 'test-key', 'sandbox', null, sandbox: true);
        $driver->send('08012345678', 'Test');

        $http->assertSent(fn ($request) => str_contains($request->url(), 'api.sandbox.africastalking.com'));
    }

    public function test_rejected_recipient_returns_failure_result(): void
    {
        $http = new HttpFactory();
        $http->fake(['*' => $http::response([
            'SMSMessageData' => ['Recipients' => [['status' => 'InvalidPhoneNumber']]],
        ], 200)]);

        $driver = new AfricasTalkingDriver($http, 'test-key', 'sandbox', null);
        $result = $driver->send('not-a-number', 'Test');

        $this->assertFalse($result->successful);
    }
}
