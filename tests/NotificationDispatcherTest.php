<?php

namespace Welfare\Notifications\Tests;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Welfare\Notifications\NotificationDispatcher;
use Welfare\Notifications\NotificationPayload;
use Welfare\Notifications\Push\SendPushJob;
use Welfare\Notifications\Recipient;
use Welfare\Notifications\Sms\SendSmsJob;

class NotificationDispatcherTest extends TestCase
{
    public function test_sends_sms_and_push_when_recipient_opted_into_both(): void
    {
        $bus = new RecordingBusDispatcher();
        $dispatcher = $this->makeDispatcher($bus);

        $recipient = new Recipient(
            email: 'jane@example.com',
            phoneNumber: '08012345678',
            pushToken: 'fcm-token-abc',
            wantsEmail: true,
            wantsSms: true,
            wantsPush: true,
        );

        $dispatcher->send($recipient, new NotificationPayload(title: 'Credit Alert', body: 'You received ₦5,000.00'));

        $this->assertTrue($bus->wasDispatched(SendSmsJob::class));
        $this->assertTrue($bus->wasDispatched(SendPushJob::class));
    }

    public function test_does_not_sms_a_recipient_who_opted_out(): void
    {
        $bus = new RecordingBusDispatcher();
        $dispatcher = $this->makeDispatcher($bus);

        $recipient = new Recipient(
            email: 'jane@example.com',
            phoneNumber: '08012345678',
            pushToken: 'fcm-token-abc',
            wantsEmail: true,
            wantsSms: false,
            wantsPush: true,
        );

        $dispatcher->send($recipient, new NotificationPayload(title: 'Credit Alert', body: 'Body'));

        $this->assertFalse($bus->wasDispatched(SendSmsJob::class));
        $this->assertTrue($bus->wasDispatched(SendPushJob::class));
    }

    public function test_never_dispatches_sms_or_push_without_contact_details_even_if_opted_in(): void
    {
        $bus = new RecordingBusDispatcher();
        $dispatcher = $this->makeDispatcher($bus);

        $recipient = new Recipient(
            email: 'jane@example.com',
            phoneNumber: null,
            pushToken: null,
            wantsEmail: true,
            wantsSms: true,
            wantsPush: true,
        );

        $dispatcher->send($recipient, new NotificationPayload(title: 'Credit Alert', body: 'Body'));

        $this->assertFalse($bus->wasDispatched(SendSmsJob::class));
        $this->assertFalse($bus->wasDispatched(SendPushJob::class));
    }

    public function test_force_bypasses_push_opt_out_but_never_sms(): void
    {
        $bus = new RecordingBusDispatcher();
        $dispatcher = $this->makeDispatcher($bus);

        $recipient = new Recipient(
            email: 'jane@example.com',
            phoneNumber: '08012345678',
            pushToken: 'fcm-token-abc',
            wantsEmail: false,
            wantsSms: false,
            wantsPush: false,
        );

        $dispatcher->send($recipient, new NotificationPayload(title: 'Account Frozen', body: 'Body'), force: true);

        $this->assertFalse($bus->wasDispatched(SendSmsJob::class));
        $this->assertTrue($bus->wasDispatched(SendPushJob::class));
    }

    public function test_sms_uses_the_short_override_body_when_provided(): void
    {
        $bus = new RecordingBusDispatcher();
        $dispatcher = $this->makeDispatcher($bus);

        $recipient = new Recipient(
            email: null,
            phoneNumber: '08012345678',
            pushToken: null,
            wantsEmail: false,
            wantsSms: true,
            wantsPush: false,
        );

        $dispatcher->send($recipient, new NotificationPayload(
            title: 'Credit Alert',
            body: 'This is a much longer body meant for push/email, not the 160-char SMS budget.',
            smsBody: 'Short SMS body',
        ));

        /** @var SendSmsJob $job */
        $job = $bus->dispatched(SendSmsJob::class)[0];
        $this->assertSame('Short SMS body', $job->message);
    }

    public function test_send_sms_now_delivers_immediately_without_a_recipient_or_preference_check(): void
    {
        $bus = new RecordingBusDispatcher();
        $dispatcher = $this->makeDispatcher($bus);

        $result = $dispatcher->sendSmsNow('08012345678', 'Your OTP is 123456');

        // No job queued — this bypasses the Bus entirely, unlike send().
        $this->assertFalse($bus->wasDispatched(SendSmsJob::class));
        $this->assertSame('termii', $result->provider);
    }
}

/**
 * Minimal in-memory Bus\Dispatcher fake — no queue, no container, just
 * records what was handed to it. Illuminate\Support\Testing\Fakes\BusFake
 * would work too but needs a fully wired facade/container; this is enough
 * for what NotificationDispatcher needs to prove.
 */
final class RecordingBusDispatcher implements BusDispatcher
{
    private array $dispatched = [];

    public function dispatch($command)
    {
        $this->dispatched[get_class($command)][] = $command;

        return null;
    }

    public function dispatchSync($command, $handler = null)
    {
        return $this->dispatch($command);
    }

    public function dispatchNow($command, $handler = null)
    {
        return $this->dispatch($command);
    }

    public function hasCommandHandler($command)
    {
        return false;
    }

    public function getCommandHandler($command)
    {
        return false;
    }

    public function pipeThrough(array $pipes)
    {
        return $this;
    }

    public function map(array $map)
    {
        return $this;
    }

    public function wasDispatched(string $jobClass): bool
    {
        return !empty($this->dispatched[$jobClass]);
    }

    public function dispatched(string $jobClass): array
    {
        return $this->dispatched[$jobClass] ?? [];
    }
}
