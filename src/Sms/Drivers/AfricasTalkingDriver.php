<?php

namespace Welfare\Notifications\Sms\Drivers;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;
use Welfare\Notifications\Sms\Contracts\SmsProvider;
use Welfare\Notifications\Support\PhoneNumber;
use Welfare\Notifications\Support\SendResult;

/**
 * Africa's Talking (https://africastalking.com) SMS gateway.
 * API docs: https://developers.africastalking.com/docs/sms/sending
 */
final class AfricasTalkingDriver implements SmsProvider
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $apiKey,
        private readonly string $username,
        private readonly ?string $senderId,
        private readonly bool $sandbox = false,
    ) {
    }

    public function send(string $to, string $message): SendResult
    {
        $baseUrl = $this->sandbox
            ? 'https://api.sandbox.africastalking.com/version1/messaging'
            : 'https://api.africastalking.com/version1/messaging';

        try {
            $payload = [
                'username' => $this->username,
                'to' => PhoneNumber::toE164($to),
                'message' => $message,
            ];

            if (!empty($this->senderId)) {
                $payload['from'] = $this->senderId;
            }

            $response = $this->http
                ->timeout(10)
                ->asForm()
                ->withHeaders([
                    'apiKey' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->post($baseUrl, $payload);

            $recipients = $response->json('SMSMessageData.Recipients') ?? [];
            $status = $recipients[0]['status'] ?? null;
            $successful = $response->successful() && $status === 'Success';

            if (!$successful) {
                Log::warning('Africa\'s Talking SMS send failed', [
                    'to' => PhoneNumber::mask($to),
                    'status' => $response->status(),
                    'recipient_status' => $status,
                ]);
            }

            return $successful
                ? SendResult::success('africastalking')
                : SendResult::failure('africastalking', $status ?? 'Africa\'s Talking rejected the request');
        } catch (\Throwable $e) {
            Log::error('Africa\'s Talking SMS send threw', ['to' => PhoneNumber::mask($to), 'error' => $e->getMessage()]);

            return SendResult::failure('africastalking', 'Request to Africa\'s Talking failed');
        }
    }
}
