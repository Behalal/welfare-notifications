<?php

namespace Welfare\Notifications\Push\Drivers;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Welfare\Notifications\Events\PushTokenStale;
use Welfare\Notifications\Push\Contracts\PushProvider;
use Welfare\Notifications\Support\SendResult;

/**
 * Firebase Cloud Messaging via the Firebase Admin SDK (kreait/firebase-php).
 *
 * Replaces the previous per-app 'firebase' notification-channel pattern,
 * which relied on a Laravel notification channel class that never actually
 * existed — any send fatal-errored. This driver calls the SDK directly, no
 * indirection.
 */
final class FirebaseDriver implements PushProvider
{
    private ?Messaging $messaging = null;

    public function __construct(
        private readonly string $credentialsPath,
        private readonly ?EventDispatcher $events = null,
    ) {
    }

    private function messaging(): Messaging
    {
        if ($this->messaging === null) {
            $this->messaging = (new Factory)
                ->withServiceAccount($this->credentialsPath)
                ->createMessaging();
        }

        return $this->messaging;
    }

    public function send(string $token, string $title, string $body, array $data = []): SendResult
    {
        try {
            $message = CloudMessage::new()
                ->withToken($token)
                ->withNotification(FirebaseNotification::create($title, $body))
                ->withData($data);

            $this->messaging()->send($message);

            return SendResult::success('firebase');
        } catch (NotFound $e) {
            Log::info('FCM token no longer valid');
            $this->events?->dispatch(new PushTokenStale($token));

            return SendResult::failure('firebase', 'Token no longer registered');
        } catch (\Throwable $e) {
            Log::error('Firebase push send failed', ['error' => $e->getMessage(), 'title' => $title]);

            return SendResult::failure('firebase', 'Request to Firebase failed');
        }
    }
}
