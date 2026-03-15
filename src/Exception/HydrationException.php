<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Exception;

use function get_debug_type;
use function is_scalar;
use function sprintf;
use function strrpos;
use function substr;

final class HydrationException extends CreemException
{
    public static function missingField(string $dto, string $field): self
    {
        return self::forField($dto, $field, 'field is required');
    }

    public static function invalidField(string $dto, string $field, string $expected, mixed $value = null): self
    {
        return self::forField(
            $dto,
            $field,
            sprintf('expected %s, got %s', $expected, get_debug_type($value)),
            $value,
        );
    }

    public static function forField(string $dto, string $field, string $reason, mixed $value = null): self
    {
        $dtoName = self::normalizeDtoName($dto);
        $context = [
            'dto' => $dtoName,
            'field' => $field,
            'reason' => $reason,
        ];

        if ($value !== null) {
            $context['actual_type'] = get_debug_type($value);

            if (is_scalar($value)) {
                $context['actual_value'] = $value;
            }
        }

        return new self(
            sprintf('Hydration failed for %s.%s: %s.', $dtoName, $field, $reason),
            null,
            $context,
        );
    }

    private static function normalizeDtoName(string $dto): string
    {
        $separatorPosition = strrpos($dto, '\\');

        if ($separatorPosition === false) {
            return $dto;
        }

        return substr($dto, $separatorPosition + 1);
    }
}
