<?php

namespace Welfare\Notifications\Support;

/**
 * Outcome of a single send attempt. Every provider driver returns one of
 * these instead of throwing — a provider outage or bad credential should
 * never be able to fail the caller's queue job (or, if ever invoked
 * synchronously, the caller's HTTP request). Callers/jobs are expected to
 * log failures using this object rather than a caught exception.
 */
final class SendResult
{
    private function __construct(
        public readonly bool $successful,
        public readonly string $provider,
        /** Short, human-readable reason — never the raw provider response body, which may echo request data. */
        public readonly ?string $message = null,
        /**
         * Optional provider-specific extras a caller might need (e.g.
         * Termii's remaining wallet balance, for a low-credit alert).
         * Deliberately a loose bag rather than typed fields — different
         * providers expose different bookkeeping, and most callers never
         * need this at all.
         */
        public readonly array $meta = [],
    ) {
    }

    public static function success(string $provider, ?string $message = null, array $meta = []): self
    {
        return new self(true, $provider, $message, $meta);
    }

    public static function failure(string $provider, ?string $message = null, array $meta = []): self
    {
        return new self(false, $provider, $message, $meta);
    }
}
