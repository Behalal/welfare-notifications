<?php

namespace Welfare\Notifications;

/**
 * What to send, independent of channel or provider. Per-channel overrides
 * are optional — when omitted, a channel falls back to $body/$title.
 *
 * Scope note: this package only carries SMS + push content. Email is
 * deliberately NOT part of this payload — Laravel's own Mail system
 * already provides swappable transports (MAIL_MAILER), and email templates
 * are naturally app/product-specific (branding differs per product), so
 * each host app sends its own email directly via Mail::, alongside calling
 * this package's dispatcher for SMS/push. Keeping that split means this
 * package has exactly one job: SMS + push fan-out.
 */
final readonly class NotificationPayload
{
    public function __construct(
        /** Push notification title. */
        public string $title,
        /** Default body — used for push and as the SMS body unless $smsBody is set. */
        public string $body,
        /** Optional override for SMS — keep short, most providers bill per 160 chars. */
        public ?string $smsBody = null,
        /** Extra data delivered alongside the push notification (e.g. a deep link). */
        public array $pushData = [],
        /** Short identifier for the notification kind (e.g. 'credit_alert') — used for logging only, never sent to the provider. */
        public string $action = 'notification',
        /**
         * Free-form context for your own audit trail — never sent to any
         * provider, only carried through on NotificationDispatched so a
         * listener can log it wherever your app already keeps records
         * (e.g. mandate_id, amount, reference).
         */
        public array $metadata = [],
    ) {
    }

    public function smsMessage(): string
    {
        return $this->smsBody ?? $this->body;
    }
}
