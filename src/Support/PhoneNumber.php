<?php

namespace Welfare\Notifications\Support;

/**
 * Normalizes a locally-formatted Nigerian number (e.g. "08012345678") to
 * E.164 ("+2348012345678"), which every supported SMS provider expects.
 * Leaves already-international numbers (starting with "+") untouched.
 */
final class PhoneNumber
{
    public static function toE164(string $phone, string $countryCode = '234'): string
    {
        $phone = trim($phone);

        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        return '+' . $countryCode . ltrim($phone, '0');
    }

    /**
     * Last 4 digits only — for logging. Never log a full phone number.
     */
    public static function mask(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return $digits === '' ? '****' : '****' . substr($digits, -4);
    }
}
