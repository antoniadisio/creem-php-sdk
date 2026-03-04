<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Dto\Common\ExpandableResource;
use Creem\Dto\Common\StructuredObject;
use Creem\Enum\CurrencyCode;
use Creem\Exception\HydrationException;
use Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

test('it parses strict scalar types', function (): void {
    $payload = [
        'label' => 'starter',
        'count' => 3,
        'rate' => 1.5,
        'enabled' => true,
    ];

    $this->assertSame('starter', Payload::string($payload, 'label', 'ExampleDto', true));
    $this->assertSame(3, Payload::integer($payload, 'count', 'ExampleDto', true));
    $this->assertEqualsWithDelta(1.5, Payload::decimal($payload, 'rate', 'ExampleDto', true), PHP_FLOAT_EPSILON);
    $this->assertTrue(Payload::bool($payload, 'enabled', 'ExampleDto', true));
});

test('it parses enum values', function (): void {
    $currency = Payload::enum(['currency' => 'USD'], 'currency', 'StatsSummary', CurrencyCode::class, true);

    $this->assertSame(CurrencyCode::USD, $currency);
});

test('it parses iso date time strings', function (): void {
    $createdAt = Payload::dateTime(['created_at' => '2026-03-03T10:15:00+00:00'], 'created_at', 'Product', true);

    $this->assertInstanceOf(DateTimeImmutable::class, $createdAt);
    $this->assertSame('2026-03-03T10:15:00+00:00', $createdAt->format(DATE_ATOM));
});

test('it parses millisecond timestamps', function (): void {
    $timestamp = Payload::millisecondsDateTime(['timestamp' => 1700000000000], 'timestamp', 'StatsPeriod', true);

    $this->assertInstanceOf(DateTimeImmutable::class, $timestamp);
    $this->assertSame('2023-11-14T22:13:20+00:00', $timestamp->format(DATE_ATOM));
});

test('it maps typed objects lists and array objects', function (): void {
    $payload = [
        'totals' => ['total_products' => 2],
        'periods' => [
            ['id' => 'period_1'],
            ['id' => 'period_2'],
        ],
        'metadata' => ['source' => 'sdk-test'],
    ];

    $totals = Payload::typedObject(
        $payload,
        'totals',
        'StatsSummary',
        static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
        true,
    );
    $periods = Payload::typedList(
        $payload,
        'periods',
        'StatsSummary',
        static function (mixed $item): string {
            if (! is_array($item) || ! array_key_exists('id', $item) || ! is_string($item['id'])) {
                throw new \RuntimeException('Unexpected period payload.');
            }

            return $item['id'];
        },
        true,
    );
    $metadata = Payload::arrayObject($payload, 'metadata', 'Checkout', true);

    $this->assertInstanceOf(StructuredObject::class, $totals);
    $this->assertSame(2, $totals->get('total_products'));
    $this->assertSame(['period_1', 'period_2'], $periods);
    $this->assertSame(['source' => 'sdk-test'], $metadata);
});

test('it maps expandable resources from expanded object payloads', function (): void {
    $product = Payload::expandableResource(
        ['product' => ['id' => 'prod_123', 'name' => 'Starter']],
        'product',
        'Checkout',
        static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
        true,
    );

    $this->assertInstanceOf(ExpandableResource::class, $product);
    $this->assertSame('prod_123', $product->id());
    $this->assertTrue($product->isExpanded());
    $this->assertInstanceOf(StructuredObject::class, $product->resource());
});

test('it maps expandable resources from id only payloads', function (): void {
    $customer = Payload::expandableResource(
        ['customer' => 'cus_123'],
        'customer',
        'Checkout',
        static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
        true,
    );

    $this->assertInstanceOf(ExpandableResource::class, $customer);
    $this->assertSame('cus_123', $customer->id());
    $this->assertFalse($customer->isExpanded());
    $this->assertNotInstanceOf(StructuredObject::class, $customer->resource());
});

test('it throws contextual hydration exceptions for invalid required integer fields', function (): void {
    expect(static function (): void {
        Payload::integer(['price' => '4900'], 'price', 'Product', true);
    })
        ->toThrow(HydrationException::class, 'Hydration failed for Product.price: expected int, got string.');
});

test('it throws contextual hydration exceptions for missing required fields', function (): void {
    expect(static function (): void {
        Payload::dateTime([], 'created_at', 'Product', true);
    })
        ->toThrow(HydrationException::class, 'Hydration failed for Product.created_at: field is required.');
});

test('it throws contextual hydration exceptions for invalid date time strings', function (): void {
    expect(static function (): void {
        Payload::dateTime(['created_at' => 'not-a-date'], 'created_at', 'Product', true);
    })
        ->toThrow(HydrationException::class, 'Hydration failed for Product.created_at: expected a valid date-time string.');
});

test('it throws contextual hydration exceptions for invalid millisecond timestamps', function (): void {
    expect(static function (): void {
        Payload::millisecondsDateTime(['timestamp' => '1700000000000'], 'timestamp', 'StatsPeriod', true);
    })
        ->toThrow(HydrationException::class, 'Hydration failed for StatsPeriod.timestamp: expected int millisecond timestamp, got string.');
});

test('it throws contextual hydration exceptions for invalid enum values', function (): void {
    expect(static function (): void {
        Payload::enum(['currency' => 'usd'], 'currency', 'StatsSummary', CurrencyCode::class, true);
    })
        ->toThrow(HydrationException::class, 'Hydration failed for StatsSummary.currency: expected valid Creem\Enum\CurrencyCode, got string.');
});

test('it throws contextual hydration exceptions for malformed nested objects', function (): void {
    expect(static function (): void {
        Payload::typedObject(
            ['totals' => 'invalid'],
            'totals',
            'StatsSummary',
            static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
            true,
        );
    })->toThrow(HydrationException::class, 'Hydration failed for StatsSummary.totals: expected object, got string.');
});
