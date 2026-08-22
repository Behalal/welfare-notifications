# welfare/notifications

Shared SMS + push notification dispatch for Laravel apps. Built after finding the same SMS/push code duplicated (and independently buggy — one app's copy had a working Termii client, the other's had a dead one; one app's push channel referenced a Laravel notification channel class that never existed) across `welfareMFB` and `core-banking`. Extracted once, before a third app (account-as-a-service, loan-service) makes reconciling three divergent copies more expensive than extracting one.

## What this is (and isn't)

- **Is**: an SMS + push fan-out with swappable providers, selected by config/env, never by editing call sites.
- **Isn't**: an email system (Laravel's own `Mail` facade already does swappable transports — `MAIL_MAILER=ses|postmark|smtp` — no reason to duplicate that), and isn't the in-app notification inbox (Laravel's own `Notifiable`/`database` channel already covers that; nothing to abstract).
- **Isn't coupled to your User model.** The package takes a plain `Recipient` value object (email, phone, push token, three preference booleans, an opaque identifier for logs) — never an Eloquent model. Build one from whatever your app's customer/user model looks like; the package never needs to know its shape. That's what makes it reusable across apps with different schemas.

## Providers

| Channel | Driver | Env var |
|---|---|---|
| SMS | Termii (default) | `SMS_PROVIDER=termii` |
| SMS | Africa's Talking | `SMS_PROVIDER=africastalking` |
| SMS | Twilio | `SMS_PROVIDER=twilio` |
| Push | Firebase Cloud Messaging | `PUSH_PROVIDER=firebase` |

Adding a new provider: implement `Sms\Contracts\SmsProvider` (or `Push\Contracts\PushProvider`), add one `create{Name}Driver()` method to the manager, done — no call-site changes anywhere.

## Install (local path repo)

In the host app's `composer.json`:

```json
{
  "repositories": [
    { "type": "path", "url": "../welfare-notifications", "options": { "symlink": true } }
  ],
  "require": {
    "welfare/notifications": "*"
  }
}
```

Then:

```bash
composer require welfare/notifications
```

Laravel auto-discovers `Welfare\Notifications\NotificationsServiceProvider`.

## Configure

```bash
php artisan vendor:publish --tag=notifications-config
```

Fill in whichever provider's credentials you're using in `.env` — see `config/notifications.php` for the full list of env vars (`TERMII_*`, `AFRICASTALKING_*`, `TWILIO_*`, `FIREBASE_*`).

## Usage

```php
use Welfare\Notifications\NotificationDispatcher;
use Welfare\Notifications\NotificationPayload;
use Welfare\Notifications\Recipient;

$recipient = new Recipient(
    email: $user->email,
    phoneNumber: $user->phone_number,
    pushToken: $user->fcm_token,
    wantsEmail: $user->email_notifications_enabled,
    wantsSms: $user->sms_notifications_enabled,
    wantsPush: $user->push_notifications_enabled,
    identifier: $user->client_id, // safe-for-logs identifier only — never PII
);

app(NotificationDispatcher::class)->send(
    $recipient,
    new NotificationPayload(title: 'Credit Alert', body: "You've received ₦5,000.00"),
);

// Security-relevant events (account block/freeze) bypass the push/email
// opt-out — never SMS, which always costs money:
app(NotificationDispatcher::class)->send($recipient, $payload, force: true);
```

Or via the facade: `Notify::send($recipient, $payload)`.

Email stays outside this package — send it yourself alongside the call above:

```php
Mail::to($user->email)->queue(new YourAppSpecificMailable($transaction));
```

## Compliance / audit trail

The package doesn't own a delivery log — each host app already has its own audit trail it needs to keep populating (`admin_audit_logs` in core-banking, etc.), and that's a stronger source of truth for your own auditors than a shared package's opinion of one. Listen for the event instead:

```php
Event::listen(\Welfare\Notifications\Events\NotificationDispatched::class, function ($event) {
    // $event->channel ('sms'|'push'), $event->result (SendResult), $event->action, $event->recipientIdentifier
});
```

A stale/unregistered push token also fires an event rather than touching any app's user table directly:

```php
Event::listen(\Welfare\Notifications\Events\PushTokenStale::class, function ($event) {
    User::where('fcm_token', $event->token)->update(['fcm_token' => null]);
});
```

## Security notes

- No driver ever throws on a provider/network failure — every send returns a `SendResult`, so a vendor outage can never crash a queue worker or bubble into a request that triggered it.
- Phone numbers are masked (`****1234`) in every log line; message bodies and API keys/tokens are never logged.
- Twilio's Auth Token travels only in the `Authorization` header, never the URL.
- No static `Job::dispatch()` sugar and no `event()`/`app()` global helpers — those live in `illuminate/foundation`, which only a full Laravel application has. Every dependency is injected (constructor or method), which is also what keeps this package's own test suite fast and testbench-free.

## Testing this package

```bash
composer install
vendor/bin/phpunit
```

Plain PHPUnit, no `orchestra/testbench` — a `Manager` subclass only needs `$container->make('config')`, so tests build a bare `Illuminate\Container\Container` with a config repository and (where a job needs to log) a PSR `NullLogger` bound in. Firebase/kreait itself isn't mocked (no injectable HTTP client in that SDK) — what's tested there is that a misconfigured/missing credential degrades to a logged failure rather than a fatal error; the actual send is exercised by each host app's own integration tests against a real Firebase project.
