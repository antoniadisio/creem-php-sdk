<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Serialization;

use BackedEnum;
use DateTimeInterface;

use function array_filter;
use function is_array;
use function is_object;
use function method_exists;

final class RequestValueNormalizer
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function payload(array $payload): array
    {
        /** @var array<string, mixed> */
        return self::normalizeArray(self::filterNulls($payload));
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, string|int|float>
     */
    public static function query(array $query): array
    {
        /** @var array<string, string|int|float> */
        return self::normalizeArray(self::filterNulls($query));
    }

    public static function rfc3339(?DateTimeInterface $value): ?string
    {
        return $value?->format(DateTimeInterface::RFC3339);
    }

    public static function unixMilliseconds(?DateTimeInterface $value): ?int
    {
        if (! $value instanceof \DateTimeInterface) {
            return null;
        }

        return (int) $value->format('Uv');
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private static function normalizeArray(array $values): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            $normalized[$key] = self::normalizeValue($value);
        }

        return $normalized;
    }

    private static function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return self::rfc3339($value);
        }

        if (is_array($value)) {
            return self::normalizeArray($value);
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            /** @var array<array-key, mixed> $arrayValue */
            $arrayValue = $value->toArray();

            return self::normalizeArray($arrayValue);
        }

        return $value;
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private static function filterNulls(array $values): array
    {
        return array_filter($values, static fn (mixed $value): bool => $value !== null);
    }
}
