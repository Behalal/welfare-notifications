<?php

namespace Welfare\Notifications;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Welfare\Notifications\Push\SendPushJob;
use Welfare\Notifications\Sms\SendSmsJob;

/**
 * Single entry point for outbound SMS/push notifications. Every send in
 * every host app goes through here so:
 *
 *   1. The recipient's channel preferences are honoured uniformly — no
 *      caller can accidentally bypass an opt-out.
 *   2. A failure in one channel never blocks the other (each channel is
 *      its own queued job).
 *   3. Swapping providers (Termii -> Africa's Talking, say) or adding a new
 *      one never requires touching call sites — only SmsManager/PushManager.
 *
 * Not handled here: email (use Laravel's own Mail:: — see NotificationPayload's
 * docblock) and the in-app inbox (use Laravel's own Notifiable/database
 * channel in the host app — nothing to abstract, it's already framework-native).
 *
 * Jobs are pushed via an injected Bus dispatcher rather than the static
 * Job::dispatch() sugar — that sugar lives in illuminate/foundation, which
 * only a full Laravel application has, not a library package. The host
 * app's container supplies this automatically (every Laravel app already
 * binds Illuminate\Contracts\Bus\Dispatcher).
 */
final class NotificationDispatcher
{
    public function __construct(
        private readonly BusDispatcher $bus,
    ) {
    }

    /**
     * Send $payload to $recipient over every channel they're reachable on
     * and have opted into.
     *
     * @param bool $force Bypass the recipient's push preference — for
     *   security-relevant events (e.g. account block) that must reach the
     *   customer regardless of opt-out. Never bypasses SMS, which always
     *   carries a per-message cost and is never appropriate to force.
     */
    public function send(Recipient $recipient, NotificationPayload $payload, bool $force = false): void
    {
        if (!empty($recipient->phoneNumber) && $recipient->wantsSms) {
            $this->bus->dispatch(new SendSmsJob(
                $recipient->phoneNumber,
                $payload->smsMessage(),
                $payload->action,
                $recipient->identifier,
                $payload->metadata,
            ));
        }

        if (!empty($recipient->pushToken) && ($force || $recipient->wantsPush)) {
            $this->bus->dispatch(new SendPushJob(
                $recipient->pushToken,
                $payload->title,
                $payload->body,
                $payload->pushData,
                $payload->action,
                $recipient->identifier,
                $payload->metadata,
            ));
        }
    }
}
