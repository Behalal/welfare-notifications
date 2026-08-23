<?php

namespace Welfare\Notifications\Sms\Drivers;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;
use Welfare\Notifications\Sms\Contracts\SmsProvider;
use Welfare\Notifications\Support\PhoneNumber;
use Welfare\Notifications\Support\SendResult;

/**
 * Termii (https://termii.com) — the SMS gateway both host apps already use
 * in production. See config/notifications.php for the required keys.
 */
final class TermiiDriver implements SmsProvider
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $apiKey,
        private readonly string $senderId,
        private readonly string $endpoint,
        private readonly string $channel = 'dnd',
    ) {
    }

    public function send(string $to, string $message): SendResult
    {
        try {
            $response = $this->http
                ->timeout(10)
                ->withHeaders(['Accept' => 'application/json'])
                ->asJson()
                ->post(rtrim($this->endpoint, '/') . '/api/sms/send', [
                    'api_key' => $this->apiKey,
                    // Termii's documented format is digits-only ("2347...", no
                    // leading "+"), unlike Twilio/Africa's Talking which want
                    // standard E.164 -- confirmed against production 400s.
                    'to' => ltrim(PhoneNumber::toE164($to), '+'),
                    'from' => $this->senderId,
                    'sms' => $message,
                    'type' => 'plain',
                    'channel' => $this->channel,
                ]);

            $body = $response->json();
            $successful = $response->successful() && (($body['code'] ?? null) === 'ok');
            $meta = isset($body['balance']) ? ['balance' => $body['balance']] : [];

            if (!$successful) {
                Log::warning('Termii SMS send failed', [
                    'to' => PhoneNumber::mask($to),
                    'status' => $response->status(),
                ]);
            }

            return $successful
                ? SendResult::success('termii', meta: $meta)
                : SendResult::failure('termii', $body['message'] ?? 'Termii rejected the request', $meta);
        } catch (\Throwable $e) {
            Log::error('Termii SMS send threw', ['to' => PhoneNumber::mask($to), 'error' => $e->getMessage()]);

            return SendResult::failure('termii', 'Request to Termii failed');
        }
    }
}
