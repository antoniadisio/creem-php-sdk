<?php

declare(strict_types=1);

namespace Creem\Internal\Http\Requests;

use InvalidArgumentException;

use function in_array;
use function preg_match;
use function sprintf;
use function trim;

final class PathIdentifier
{
    private const string RESERVED_OR_CONTROL_PATTERN = '/[\/\\\\?#%\x00-\x1F\x7F]/';

    private const string ALLOWED_PATTERN = '/^[A-Za-z0-9._-]+$/';

    public static function normalize(string $value, string $label): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException(sprintf('The %s cannot be blank.', $label));
        }

        if (in_array($value, ['.', '..'], true)) {
            throw new InvalidArgumentException(sprintf('The %s cannot be "." or "..".', $label));
        }

        if (preg_match(self::RESERVED_OR_CONTROL_PATTERN, $value) === 1) {
            throw new InvalidArgumentException(
                sprintf('The %s cannot contain reserved URI characters or control characters.', $label),
            );
        }

        if (preg_match(self::ALLOWED_PATTERN, $value) !== 1) {
            throw new InvalidArgumentException(
                sprintf('The %s contains unsupported characters. Allowed characters are letters, numbers, ".", "_", and "-".', $label),
            );
        }

        return $value;
    }
}
