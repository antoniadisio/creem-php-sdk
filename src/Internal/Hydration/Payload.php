<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Hydration;

use Antoniadisio\Creem\Dto\Common\ExpandableResource;
use Antoniadisio\Creem\Dto\Common\Page;
use Antoniadisio\Creem\Dto\Common\Pagination;
use Antoniadisio\Creem\Dto\Common\StructuredList;
use Antoniadisio\Creem\Dto\Common\StructuredObject;
use Antoniadisio\Creem\Exception\HydrationException;
use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

use function array_is_list;
use function array_key_exists;
use function intdiv;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;
use function sprintf;

final class Payload
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function string(array $payload, string $key, ?string $dto = null, bool $required = false): ?string
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (! self::isStrict($dto, $required)) {
            return null;
        }

        throw HydrationException::invalidField(self::dtoName($dto), $key, 'string', $value);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function number(array $payload, string $key, ?string $dto = null, bool $required = false): int|float|null
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (! is_numeric($value)) {
            if (! self::isStrict($dto, $required)) {
                return null;
            }

            throw HydrationException::invalidField(self::dtoName($dto), $key, 'int|float', $value);
        }

        if (self::isStrict($dto, $required)) {
            throw HydrationException::invalidField(self::dtoName($dto), $key, 'int|float', $value);
        }

        return is_float($value + 0) ? (float) $value : (int) $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function bool(array $payload, string $key, ?string $dto = null, bool $required = false): ?bool
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (! self::isStrict($dto, $required)) {
            return null;
        }

        throw HydrationException::invalidField(self::dtoName($dto), $key, 'bool', $value);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function object(array $payload, string $key, ?string $dto = null, bool $required = false): ?StructuredObject
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (! is_array($value) || array_is_list($value)) {
            if (! self::isStrict($dto, $required)) {
                return null;
            }

            throw HydrationException::invalidField(self::dtoName($dto), $key, 'object', $value);
        }

        /** @var array<string, mixed> $value */
        return StructuredObject::fromArray($value);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function list(array $payload, string $key, ?string $dto = null, bool $required = false): StructuredList
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return StructuredList::fromArray([]);
        }

        if (! is_array($value) || ! array_is_list($value)) {
            if (! self::isStrict($dto, $required)) {
                return StructuredList::fromArray([]);
            }

            throw HydrationException::invalidField(self::dtoName($dto), $key, 'list', $value);
        }

        return StructuredList::fromArray($value);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public static function arrayObject(array $payload, string $key, string $dto, bool $required = false): ?array
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (! is_array($value) || array_is_list($value)) {
            throw HydrationException::invalidField($dto, $key, 'object', $value);
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function integer(array $payload, string $key, string $dto, bool $required = false): ?int
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (! is_int($value)) {
            throw HydrationException::invalidField($dto, $key, 'int', $value);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function decimal(array $payload, string $key, string $dto, bool $required = false): ?float
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (! is_int($value) && ! is_float($value)) {
            throw HydrationException::invalidField($dto, $key, 'float', $value);
        }

        return (float) $value;
    }

    /**
     * @template TEnum of BackedEnum
     *
     * @param  array<string, mixed>  $payload
     * @param  class-string<TEnum>  $enumClass
     * @return TEnum|null
     */
    public static function enum(
        array $payload,
        string $key,
        string $dto,
        string $enumClass,
        bool $required = false,
    ): ?BackedEnum {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (! is_string($value) && ! is_int($value)) {
            throw HydrationException::invalidField($dto, $key, sprintf('valid %s', $enumClass), $value);
        }

        $enum = $enumClass::tryFrom($value);

        if ($enum === null) {
            throw HydrationException::invalidField($dto, $key, sprintf('valid %s', $enumClass), $value);
        }

        return $enum;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function dateTime(array $payload, string $key, string $dto, bool $required = false): ?DateTimeImmutable
    {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (! is_string($value)) {
            throw HydrationException::invalidField($dto, $key, 'date-time string', $value);
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            throw HydrationException::forField($dto, $key, 'expected a valid date-time string', $value);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function millisecondsDateTime(
        array $payload,
        string $key,
        string $dto,
        bool $required = false,
    ): ?DateTimeImmutable {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (! is_int($value)) {
            throw HydrationException::invalidField($dto, $key, 'int millisecond timestamp', $value);
        }

        $seconds = intdiv($value, 1000);
        $microseconds = ($value % 1000) * 1000;
        $timestamp = DateTimeImmutable::createFromFormat(
            'U.u',
            sprintf('%d.%06d', $seconds, $microseconds),
            new DateTimeZone('UTC'),
        );

        if ($timestamp === false) {
            throw HydrationException::forField($dto, $key, 'expected a valid millisecond timestamp', $value);
        }

        return $timestamp;
    }

    /**
     * @template TObject of object
     *
     * @param  array<string, mixed>  $payload
     * @param  callable(array<string, mixed>): TObject  $mapper
     * @return TObject|null
     */
    public static function typedObject(
        array $payload,
        string $key,
        string $dto,
        callable $mapper,
        bool $required = false,
    ): ?object {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (! is_array($value) || array_is_list($value)) {
            throw HydrationException::invalidField($dto, $key, 'object', $value);
        }

        /** @var array<string, mixed> $value */
        /** @var TObject $object */
        $object = $mapper($value);

        return $object;
    }

    /**
     * @template TItem
     *
     * @param  array<string, mixed>  $payload
     * @param  callable(mixed): TItem  $mapper
     * @return list<TItem>
     */
    public static function typedList(
        array $payload,
        string $key,
        string $dto,
        callable $mapper,
        bool $required = false,
    ): array {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return [];
        }

        if (! is_array($value) || ! array_is_list($value)) {
            throw HydrationException::invalidField($dto, $key, 'list', $value);
        }

        $mapped = [];

        foreach ($value as $item) {
            $mapped[] = $mapper($item);
        }

        return $mapped;
    }

    /**
     * @template TObject of object
     *
     * @param  array<string, mixed>  $payload
     * @param  callable(array<string, mixed>): TObject  $mapper
     * @return ExpandableResource<TObject>|null
     */
    public static function expandableResource(
        array $payload,
        string $key,
        string $dto,
        callable $mapper,
        bool $required = false,
    ): ?ExpandableResource {
        $value = self::value($payload, $key, $dto, $required);

        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            /** @var ExpandableResource<TObject> $resource */
            $resource = ExpandableResource::fromId($value);

            return $resource;
        }

        if (! is_array($value) || array_is_list($value)) {
            throw HydrationException::invalidField($dto, $key, 'expandable resource string or object', $value);
        }

        /** @var array<string, mixed> $value */
        $id = self::string($value, 'id', $dto, true);

        if ($id === null) {
            throw HydrationException::missingField($dto, $key);
        }

        /** @var ExpandableResource<TObject> $resource */
        $resource = ExpandableResource::expanded($id, $mapper($value));

        return $resource;
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
        $items = self::typedList(
            $payload,
            'items',
            Page::class,
            static function (mixed $item) use ($mapper): mixed {
                if (! is_array($item) || array_is_list($item)) {
                    throw HydrationException::invalidField(Page::class, 'items', 'object', $item);
                }

                /** @var array<string, mixed> $item */
                return $mapper($item);
            },
            true,
        );

        return new Page($items, self::pagination($payload, true));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function pagination(array $payload, bool $required = false): ?Pagination
    {
        $value = self::arrayObject($payload, 'pagination', Pagination::class, $required);

        if ($value === null) {
            return null;
        }

        foreach (['total_records', 'total_pages', 'current_page', 'next_page', 'prev_page'] as $field) {
            if (! array_key_exists($field, $value)) {
                throw HydrationException::missingField(Pagination::class, $field);
            }
        }

        return new Pagination(
            self::integer($value, 'total_records', Pagination::class, true),
            self::integer($value, 'total_pages', Pagination::class, true),
            self::integer($value, 'current_page', Pagination::class, true),
            self::integer($value, 'next_page', Pagination::class),
            self::integer($value, 'prev_page', Pagination::class),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function value(array $payload, string $key, ?string $dto, bool $required): mixed
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null) {
            if ($required) {
                throw HydrationException::missingField(self::dtoName($dto), $key);
            }

            return null;
        }

        return $payload[$key];
    }

    private static function dtoName(?string $dto): string
    {
        return $dto ?? 'payload';
    }

    private static function isStrict(?string $dto, bool $required): bool
    {
        return $dto !== null || $required;
    }
}
