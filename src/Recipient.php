<?php

namespace Welfare\Notifications;

/**
 * A plain, framework-agnostic description of who a notification goes to.
 *
 * Deliberately NOT an Eloquent model and NOT an interface the host app's
 * User model has to implement. Every host app (welfareMFB, core-banking,
 * and any future service — account-as-a-service, loan-service) builds one
 * of these from its own model at the call site. That is the whole point:
 * this package never needs to know what a "customer" looks like in any
 * given app, only these five facts about where/whether to reach them.
 *
 * Build one with Recipient::fromArray() or the constructor directly.
 */
final class Recipient
{
    public function __construct(
        public readonly ?string $email,
        public readonly ?string $phoneNumber,
        public readonly ?string $pushToken,
        public readonly bool $wantsEmail,
        public readonly bool $wantsSms,
        public readonly bool $wantsPush,
        /**
         * Any stable identifier safe to put in logs (e.g. client_id). Never
         * put an email or phone number here — see the security note on
         * SendSmsJob/SendPushJob about what gets logged.
         */
        public readonly ?string $identifier = null,
    ) {
    }

    /**
     * @param array{email?: ?string, phone_number?: ?string, push_token?: ?string, wants_email?: bool, wants_sms?: bool, wants_push?: bool, identifier?: ?string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'] ?? null,
            phoneNumber: $data['phone_number'] ?? null,
            pushToken: $data['push_token'] ?? null,
            wantsEmail: $data['wants_email'] ?? true,
            wantsSms: $data['wants_sms'] ?? true,
            wantsPush: $data['wants_push'] ?? true,
            identifier: $data['identifier'] ?? null,
        );
    }
}
