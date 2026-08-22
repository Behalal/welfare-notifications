<?php

namespace Welfare\Notifications\Push\Contracts;

use Welfare\Notifications\Support\SendResult;

/**
 * Every push provider driver implements this. Same rule as SmsProvider:
 * never throw on a provider/network failure, always return SendResult.
 */
interface PushProvider
{
    public function send(string $token, string $title, string $body, array $data = []): SendResult;
}
