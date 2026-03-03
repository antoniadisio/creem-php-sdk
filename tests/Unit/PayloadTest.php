<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Dto\Common\ExpandableResource;
use Creem\Dto\Common\StructuredObject;
use Creem\Enum\CurrencyCode;
use Creem\Exception\HydrationException;
use Creem\Internal\Hydration\Payload;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class PayloadTest extends TestCase
{
    public function test_it_parses_strict_scalar_types(): void
    {
        $payload = [
            'label' => 'starter',
            'count' => 3,
            'rate' => 1.5,
            'enabled' => true,
        ];

        self::assertSame('starter', Payload::string($payload, 'label', 'ExampleDto', true));
        self::assertSame(3, Payload::integer($payload, 'count', 'ExampleDto', true));
        self::assertSame(1.5, Payload::decimal($payload, 'rate', 'ExampleDto', true));
        self::assertTrue(Payload::bool($payload, 'enabled', 'ExampleDto', true));
    }

    public function test_it_parses_enums_and_dates(): void
    {
        $payload = [
            'currency' => 'USD',
            'created_at' => '2026-03-03T10:15:00+00:00',
            'timestamp' => 1700000000000,
        ];

        $currency = Payload::enum($payload, 'currency', 'StatsSummary', CurrencyCode::class, true);
        $createdAt = Payload::dateTime($payload, 'created_at', 'Product', true);
        $timestamp = Payload::millisecondsDateTime($payload, 'timestamp', 'StatsPeriod', true);

        self::assertSame(CurrencyCode::USD, $currency);
        self::assertInstanceOf(DateTimeImmutable::class, $createdAt);
        self::assertSame('2026-03-03T10:15:00+00:00', $createdAt?->format(DATE_ATOM));
        self::assertInstanceOf(DateTimeImmutable::class, $timestamp);
        self::assertSame('2023-11-14T22:13:20+00:00', $timestamp?->format(DATE_ATOM));
    }

    public function test_it_maps_typed_objects_lists_and_expandable_resources(): void
    {
        $payload = [
            'totals' => ['total_products' => 2],
            'periods' => [
                ['id' => 'period_1'],
                ['id' => 'period_2'],
            ],
            'product' => ['id' => 'prod_123', 'name' => 'Starter'],
            'customer' => 'cus_123',
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
            static fn (mixed $item): string => is_array($item) ? (string) ($item['id'] ?? '') : '',
            true,
        );
        $product = Payload::expandableResource(
            $payload,
            'product',
            'Checkout',
            static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
            true,
        );
        $customer = Payload::expandableResource(
            $payload,
            'customer',
            'Checkout',
            static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
        );
        $metadata = Payload::arrayObject($payload, 'metadata', 'Checkout', true);

        self::assertInstanceOf(StructuredObject::class, $totals);
        self::assertSame(2, $totals?->get('total_products'));
        self::assertSame(['period_1', 'period_2'], $periods);
        self::assertInstanceOf(ExpandableResource::class, $product);
        self::assertSame('prod_123', $product?->id());
        self::assertTrue($product?->isExpanded() ?? false);
        self::assertInstanceOf(StructuredObject::class, $product?->resource());
        self::assertSame('cus_123', $customer?->id());
        self::assertFalse($customer?->isExpanded() ?? true);
        self::assertSame(['source' => 'sdk-test'], $metadata);
    }

    public function test_it_throws_contextual_hydration_exceptions_for_invalid_required_fields(): void
    {
        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage('Hydration failed for Product.price: expected int, got string.');

        Payload::integer(['price' => '4900'], 'price', 'Product', true);
    }

    public function test_it_throws_contextual_hydration_exceptions_for_missing_required_fields(): void
    {
        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage('Hydration failed for Product.created_at: field is required.');

        Payload::dateTime([], 'created_at', 'Product', true);
    }

    public function test_it_throws_contextual_hydration_exceptions_for_invalid_enum_values(): void
    {
        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage('Hydration failed for StatsSummary.currency: expected valid Creem\Enum\CurrencyCode, got string.');

        Payload::enum(['currency' => 'usd'], 'currency', 'StatsSummary', CurrencyCode::class, true);
    }
}
