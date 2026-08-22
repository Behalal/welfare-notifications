<?php

namespace Welfare\Notifications\Sms;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Welfare\Notifications\Events\NotificationDispatched;
use Welfare\Notifications\Support\PhoneNumber;

/**
 * Queued so a slow/unreliable SMS gateway never blocks the request or the
 * transaction that triggered the notification. Retries with backoff for
 * transient failures (network blips, provider rate limiting); a permanent
 * rejection (bad number, provider outage) still fails after $tries without
 * ever throwing back into the caller — see SmsProvider's contract.
 *
 * Security: only a masked phone number and provider name are logged —
 * never the message body (may contain a customer's balance/PII) and never
 * the API key (drivers never put it anywhere the SendResult message could
 * echo it back).
 *
 * No static ::dispatch() sugar and no event()/app() helpers — those live in
 * illuminate/foundation, which only a full Laravel application has, not a
 * library package. NotificationDispatcher pushes this job via an injected
 * Illuminate\Contracts\Bus\Dispatcher; the event above is fired via a
 * method-injected Illuminate\Contracts\Events\Dispatcher (Laravel's queue
 * worker resolves handle()'s type-hints automatically, same as it does for
 * SmsManager below).
 */
class SendSmsJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];
    public $timeout = 30;

    public function __construct(
        public readonly string $phoneNumber,
        public readonly string $message,
        public readonly string $action = 'notification',
        public readonly ?string $recipientIdentifier = null,
    ) {
    }

    public function handle(SmsManager $sms, EventDispatcher $events): void
    {
        $result = $sms->driver()->send($this->phoneNumber, $this->message);

        $events->dispatch(new NotificationDispatched('sms', $result, $this->action, $this->recipientIdentifier));

        if (!$result->successful) {
            Log::warning('SMS notification not delivered', [
                'to' => PhoneNumber::mask($this->phoneNumber),
                'provider' => $result->provider,
                'reason' => $result->message,
                'action' => $this->action,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendSmsJob exhausted retries', [
            'to' => PhoneNumber::mask($this->phoneNumber),
            'action' => $this->action,
            'error' => $exception->getMessage(),
        ]);
    }
}
