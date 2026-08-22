<?php

namespace Welfare\Notifications\Events;

use Welfare\Notifications\Support\SendResult;

/**
 * Fired after every SMS/push send attempt, success or failure. This is the
 * hook for compliance/audit trails: each host app already has its own audit
 * log system (e.g. core-banking's admin_audit_logs) — listen for this event
 * and write into whichever system your auditors already trust. The package
 * intentionally does not own or centralize that record itself.
 */
final class NotificationDispatched
{
    public function __construct(
        public readonly string $channel,
        public readonly SendResult $result,
        public readonly string $action,
        public readonly ?string $recipientIdentifier,
    ) {
    }
}
