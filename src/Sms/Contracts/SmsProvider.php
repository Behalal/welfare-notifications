<?php

namespace Welfare\Notifications\Sms\Contracts;

use Welfare\Notifications\Support\SendResult;

/**
 * Every SMS provider driver (Termii, Africa's Talking, Twilio, ...)
 * implements this. Nothing outside Sms/ ever depends on a concrete
 * provider class — only this contract, resolved by SmsManager.
 *
 * A driver MUST NOT throw on a provider/network failure — catch and
 * return SendResult::failure(). Throwing is reserved for programmer
 * error (e.g. missing required config), which should fail loudly at
 * boot/config time, not silently at send time.
 */
interface SmsProvider
{
    /**
     * Send one SMS. $to is expected to already be in the provider's
     * expected format — see PhoneNumber::normalize() in each driver.
     */
    public function send(string $to, string $message): SendResult;
}
