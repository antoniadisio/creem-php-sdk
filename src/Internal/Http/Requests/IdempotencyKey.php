<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests;

use InvalidArgumentException;

use function preg_match;
use function trim;

/** @internal */
final class IdempotencyKey
{
    private const string HEADER_NAME = 'Idempotency-Key';

    /**
     * @return array<string, string>
     */
    public static function header(?string $idempotencyKey): array
    {
        if ($idempotencyKey === null) {
            return [];
        }

        return [self::HEADER_NAME => $idempotencyKey];
    }

    public static function normalize(?string $idempotencyKey): ?string
    {
        if ($idempotencyKey === null) {
            return null;
        }

        $idempotencyKey = trim($idempotencyKey);

        if ($idempotencyKey === '') {
            throw new InvalidArgumentException('The Creem idempotency key cannot be blank.');
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $idempotencyKey)) {
            throw new InvalidArgumentException('The Creem idempotency key cannot contain control characters.');
        }

        return $idempotencyKey;
    }
}
