<?php

declare(strict_types=1);

namespace Creem\Internal\Webhook;

use function hash_hmac;

final class Signature
{
    public static function compute(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }
}
