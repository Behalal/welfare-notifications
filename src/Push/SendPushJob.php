<?php

namespace Welfare\Notifications\Push;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Welfare\Notifications\Events\NotificationDispatched;

/**
 * No static ::dispatch() sugar and no event() helper — see SendSmsJob's
 * docblock for why (both live in illuminate/foundation, which this
 * package deliberately doesn't depend on).
 */
class SendPushJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];
    public $timeout = 30;

    public function __construct(
        public readonly string $token,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
        public readonly string $action = 'notification',
        public readonly ?string $recipientIdentifier = null,
        public readonly array $metadata = [],
    ) {
    }

    public function handle(PushManager $push, EventDispatcher $events): void
    {
        $result = $push->driver()->send($this->token, $this->title, $this->body, $this->data);

        $events->dispatch(new NotificationDispatched('push', $result, $this->action, $this->recipientIdentifier, $this->metadata));

        if (!$result->successful) {
            Log::warning('Push notification not delivered', [
                'provider' => $result->provider,
                'reason' => $result->message,
                'action' => $this->action,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendPushJob exhausted retries', [
            'action' => $this->action,
            'error' => $exception->getMessage(),
        ]);
    }
}
