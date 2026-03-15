<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Dto\Common\ExpandableResource;
use Antoniadisio\Creem\Dto\Common\Page;
use Antoniadisio\Creem\Dto\Common\Pagination;
use Antoniadisio\Creem\Dto\Common\StructuredList;
use Antoniadisio\Creem\Dto\Common\StructuredObject;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Internal\Hydration\Payload;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;

test('payload helpers parse strict scalar types', function (): void {
    $payload = [
        'label' => 'starter',
        'count' => 3,
        'rate' => 1.5,
        'enabled' => true,
    ];

    expect(Payload::string($payload, 'label', 'ExampleDto', true))->toBe('starter')
        ->and(Payload::integer($payload, 'count', 'ExampleDto', true))->toBe(3)
        ->and(Payload::decimal($payload, 'rate', 'ExampleDto', true))->toBe(1.5)
        ->and(Payload::bool($payload, 'enabled', 'ExampleDto', true))->toBeTrue();
});

test('payload helpers apply non-strict fallbacks for invalid scalar and structured values', function (): void {
    expect(Payload::string(['label' => 123], 'label'))->toBeNull()
        ->and(Payload::number(['price' => 'not-a-number'], 'price'))->toBeNull()
        ->and(Payload::bool(['enabled' => 'yes'], 'enabled'))->toBeNull()
        ->and(Payload::object(['metadata' => ['invalid']], 'metadata'))->toBeNull()
        ->and(Payload::list(['items' => ['invalid' => true]], 'items'))->toBeInstanceOf(StructuredList::class)
        ->and(Payload::list(['items' => ['invalid' => true]], 'items')->count())->toBe(0);
});

test('payload helpers coerce numeric strings in non-strict number mode', function (): void {
    expect(Payload::number(['price' => '4900'], 'price'))->toBe(4900)
        ->and(Payload::number(['rate' => '12.5'], 'rate'))->toBe(12.5);
});

test('payload helpers parse enum and date values', function (): void {
    $stringValue = Payload::dateTime(['created_at' => '2026-03-03T10:15:00+00:00'], 'created_at', 'Product', true);
    $immutableValue = Payload::dateTime(['created_at' => new DateTimeImmutable('2026-03-03T10:15:00+00:00')], 'created_at', 'Product', true);
    $mutableValue = Payload::dateTime(
        ['created_at' => new DateTime('2026-03-03T10:15:00+00:00', new DateTimeZone('UTC'))],
        'created_at',
        'Product',
        true,
    );

    expect(Payload::enum(['currency' => 'USD'], 'currency', 'StatsSummary', CurrencyCode::class, true))
        ->toBe(CurrencyCode::USD)
        ->and($stringValue)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($stringValue?->format(DATE_ATOM))->toBe('2026-03-03T10:15:00+00:00')
        ->and($immutableValue)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($immutableValue?->format(DATE_ATOM))->toBe('2026-03-03T10:15:00+00:00')
        ->and($mutableValue)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($mutableValue?->format(DATE_ATOM))->toBe('2026-03-03T10:15:00+00:00');
});

test('payload helpers parse millisecond timestamps', function (): void {
    $timestamp = Payload::millisecondsDateTime(['timestamp' => 1700000000000], 'timestamp', 'StatsPeriod', true);

    expect($timestamp)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($timestamp?->format(DATE_ATOM))->toBe('2023-11-14T22:13:20+00:00');
});

test('payload helpers map typed objects pages lists and pagination', function (): void {
    $payload = [
        'totals' => ['total_products' => 2],
        'periods' => [
            ['id' => 'period_1'],
            ['id' => 'period_2'],
        ],
        'metadata' => ['source' => 'sdk-test'],
    ];
    $pagePayload = [
        'items' => [
            ['id' => 'item_1'],
        ],
        'pagination' => [
            'total_records' => 1,
            'total_pages' => 1,
            'current_page' => 1,
            'next_page' => null,
            'prev_page' => null,
        ],
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
    $page = Payload::page(
        $pagePayload,
        static function (array $item): string {
            if (! array_key_exists('id', $item) || ! is_string($item['id'])) {
                throw new \RuntimeException('Unexpected page item payload.');
            }

            return $item['id'];
        },
    );
    $pagination = Payload::pagination($pagePayload, true);

    expect($totals)->toBeInstanceOf(StructuredObject::class)
        ->and($totals?->get('total_products'))->toBe(2)
        ->and($periods)->toBe(['period_1', 'period_2'])
        ->and($metadata)->toBe(['source' => 'sdk-test'])
        ->and($page)->toBeInstanceOf(Page::class)
        ->and($page->count())->toBe(1)
        ->and($page->get(0))->toBe('item_1')
        ->and($page->pagination)->toBeInstanceOf(Pagination::class)
        ->and($page->pagination?->currentPage)->toBe(1)
        ->and($pagination)->toBeInstanceOf(Pagination::class)
        ->and($pagination?->totalRecords)->toBe(1);
});

test('payload helpers map expandable resources from expanded objects and ids', function (): void {
    $expandedProduct = Payload::expandableResource(
        ['product' => ['id' => 'prod_123', 'name' => 'Starter']],
        'product',
        'Checkout',
        static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
        true,
    );
    $customer = Payload::expandableResource(
        ['customer' => 'cus_123'],
        'customer',
        'Checkout',
        static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
        true,
    );

    expect($expandedProduct)->toBeInstanceOf(ExpandableResource::class)
        ->and($expandedProduct?->id())->toBe('prod_123')
        ->and($expandedProduct?->isExpanded())->toBeTrue()
        ->and($expandedProduct?->resource())->toBeInstanceOf(StructuredObject::class)
        ->and($customer)->toBeInstanceOf(ExpandableResource::class)
        ->and($customer?->id())->toBe('cus_123')
        ->and($customer?->isExpanded())->toBeFalse()
        ->and($customer?->resource())->not->toBeInstanceOf(StructuredObject::class);
});
