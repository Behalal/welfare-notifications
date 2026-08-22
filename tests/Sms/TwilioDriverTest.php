<?php

namespace Welfare\Notifications\Tests\Sms;

use Illuminate\Http\Client\Factory as HttpFactory;
use Welfare\Notifications\Sms\Drivers\TwilioDriver;
use Welfare\Notifications\Tests\TestCase;

class TwilioDriverTest extends TestCase
{
    public function test_successful_send_returns_success_result(): void
    {
        $http = new HttpFactory();
        $http->fake(['api.twilio.com/*' => $http::response(['sid' => 'SM123', 'status' => 'queued'], 201)]);

        $driver = new TwilioDriver($http, 'AC_test_sid', 'test_auth_token', '+15005550006');
        $result = $driver->send('08012345678', 'Test message');

        $this->assertTrue($result->successful);
        $this->assertSame('twilio', $result->provider);
    }

    public function test_auth_token_travels_only_in_the_authorization_header_not_the_url(): void
    {
        $http = new HttpFactory();
        $http->fake(['*' => $http::response(['sid' => 'SM123'], 201)]);

        $driver = new TwilioDriver($http, 'AC_test_sid', 'super-secret-token', '+15005550006');
        $driver->send('08012345678', 'Test');

        $http->assertSent(function ($request) {
            return $request->hasHeader('Authorization')
                && !str_contains($request->url(), 'super-secret-token');
        });
    }

    public function test_provider_error_returns_failure_result(): void
    {
        $http = new HttpFactory();
        $http->fake(['*' => $http::response(['code' => 21211, 'message' => "The 'To' number is not a valid phone number."], 400)]);

        $driver = new TwilioDriver($http, 'AC_test_sid', 'test_auth_token', '+15005550006');
        $result = $driver->send('not-a-number', 'Test');

        $this->assertFalse($result->successful);
    }
}
