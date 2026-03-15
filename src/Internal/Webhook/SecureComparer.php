<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Webhook;

use function hash_equals;

final class SecureComparer
{
    public static function equals(string $expected, string $actual): bool
    {
        return hash_equals($expected, $actual);
    }
}
