<?php

declare(strict_types=1);

namespace Creem\Internal\Hydration;

use Creem\Dto\Common\ExpandableValue;
use Creem\Dto\Common\Page;
use Creem\Dto\Common\Pagination;
use Creem\Dto\Common\StructuredList;
use Creem\Dto\Common\StructuredObject;

use function array_filter;
use function array_is_list;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;

final class Payload
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function string(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function number(array $payload, string $key): int|float|null
    {
        $value = $payload[$key] ?? null;

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return is_float($value + 0) ? (float) $value : (int) $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function bool(array $payload, string $key): ?bool
    {
        $value = $payload[$key] ?? null;

        return is_bool($value) ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function object(array $payload, string $key): ?StructuredObject
    {
        $value = $payload[$key] ?? null;

        if (! is_array($value) || array_is_list($value)) {
            return null;
        }

        return StructuredObject::fromArray($value);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function list(array $payload, string $key): StructuredList
    {
        $value = $payload[$key] ?? null;

        if (! is_array($value) || ! array_is_list($value)) {
            return StructuredList::fromArray([]);
        }

        return StructuredList::fromArray($value);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function expandable(array $payload, string $key): ?ExpandableValue
    {
        return ExpandableValue::fromValue($payload[$key] ?? null);
    }

    /**
     * @template TItem
     *
     * @param  array<string, mixed>  $payload
     * @param  callable(array<string, mixed>): TItem  $mapper
     * @return Page<TItem>
     */
    public static function page(array $payload, callable $mapper): Page
    {
        $items = $payload['items'] ?? [];
        $mapped = [];

        if (is_array($items)) {
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                /** @var array<string, mixed> $item */
                $mapped[] = $mapper($item);
            }
        }

        return new Page($mapped, self::pagination($payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function pagination(array $payload): ?Pagination
    {
        $pagination = $payload['pagination'] ?? null;

        if (! is_array($pagination)) {
            return null;
        }

        $pagination = array_filter(
            $pagination,
            static fn (mixed $value): bool => true,
        );

        /** @var array<string, mixed> $pagination */
        return new Pagination(
            self::number($pagination, 'total_records'),
            self::number($pagination, 'total_pages'),
            self::number($pagination, 'current_page'),
            self::number($pagination, 'next_page'),
            self::number($pagination, 'prev_page'),
        );
    }
}
