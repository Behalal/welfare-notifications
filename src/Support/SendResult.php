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
    ) {
    }

    public static function success(string $provider, ?string $message = null): self
    {
        return new self(true, $provider, $message);
    }

    public static function failure(string $provider, ?string $message = null): self
    {
        return new self(false, $provider, $message);
    }
}
