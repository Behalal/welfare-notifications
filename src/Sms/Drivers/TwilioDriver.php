<?php

namespace Welfare\Notifications\Sms\Drivers;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;
use Welfare\Notifications\Sms\Contracts\SmsProvider;
use Welfare\Notifications\Support\PhoneNumber;
use Welfare\Notifications\Support\SendResult;

/**
 * Twilio (https://twilio.com) SMS gateway. Uses HTTP Basic Auth with the
 * Account SID as username and the Auth Token as password — standard Twilio
 * REST API convention, not a custom scheme.
 */
final class TwilioDriver implements SmsProvider
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $accountSid,
        private readonly string $authToken,
        private readonly string $fromNumber,
    ) {
    }

    public function send(string $to, string $message): SendResult
    {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";

        try {
            $response = $this->http
                ->timeout(10)
                ->asForm()
                ->withBasicAuth($this->accountSid, $this->authToken)
                ->post($url, [
                    'To' => PhoneNumber::toE164($to),
                    'From' => $this->fromNumber,
                    'Body' => $message,
                ]);

            $successful = $response->successful();

            if (!$successful) {
                Log::warning('Twilio SMS send failed', [
                    'to' => PhoneNumber::mask($to),
                    'status' => $response->status(),
                    'error_code' => $response->json('code'),
                ]);
            }

            return $successful
                ? SendResult::success('twilio')
                : SendResult::failure('twilio', $response->json('message') ?? 'Twilio rejected the request');
        } catch (\Throwable $e) {
            Log::error('Twilio SMS send threw', ['to' => PhoneNumber::mask($to), 'error' => $e->getMessage()]);

            return SendResult::failure('twilio', 'Request to Twilio failed');
        }
    }
}
