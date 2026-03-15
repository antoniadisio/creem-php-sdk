<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Dto\Common\ExpandableResource;
use Antoniadisio\Creem\Dto\Common\Page;
use Antoniadisio\Creem\Dto\Common\Pagination;
use Antoniadisio\Creem\Dto\Common\StructuredObject;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Exception\HydrationException;
use Antoniadisio\Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

foreach (payloadHydrationFailures() as $dataset => [$factory, $message]) {
    test("payload helpers throw contextual hydration exceptions for malformed input ({$dataset})", function () use ($factory, $message): void {
        expect($factory)->toThrow(HydrationException::class, $message);
    });
}

/**
 * @return array<string, array{0: callable(): mixed, 1: string}>
 */
function payloadHydrationFailures(): array
{
    return [
        'invalid required integer' => [
            static fn (): ?int => Payload::integer(['price' => '4900'], 'price', 'Product', true),
            'Hydration failed for Product.price: expected int, got string.',
        ],
        'missing required date field' => [
            static fn (): ?DateTimeImmutable => Payload::dateTime([], 'created_at', 'Product', true),
            'Hydration failed for Product.created_at: field is required.',
        ],
        'invalid date time string' => [
            static fn (): ?DateTimeImmutable => Payload::dateTime(['created_at' => 'not-a-date'], 'created_at', 'Product', true),
            'Hydration failed for Product.created_at: expected a valid date-time string.',
        ],
        'invalid millisecond timestamp' => [
            static fn (): ?DateTimeImmutable => Payload::millisecondsDateTime(['timestamp' => '1700000000000'], 'timestamp', 'StatsPeriod', true),
            'Hydration failed for StatsPeriod.timestamp: expected int millisecond timestamp, got string.',
        ],
        'invalid enum value' => [
            static fn (): mixed => Payload::enum(['currency' => 'usd'], 'currency', 'StatsSummary', CurrencyCode::class, true),
            'Hydration failed for StatsSummary.currency: expected valid Antoniadisio\Creem\Enum\CurrencyCode, got string.',
        ],
        'malformed nested object' => [
            static fn (): ?object => Payload::typedObject(
                ['totals' => 'invalid'],
                'totals',
                'StatsSummary',
                static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
                true,
            ),
            'Hydration failed for StatsSummary.totals: expected object, got string.',
        ],
        'malformed typed list' => [
            static fn (): array => Payload::typedList(
                ['periods' => 'invalid'],
                'periods',
                'StatsSummary',
                static fn (mixed $item): mixed => $item,
                true,
            ),
            'Hydration failed for StatsSummary.periods: expected list, got string.',
        ],
        'invalid expandable resource scalar' => [
            static fn (): ?ExpandableResource => Payload::expandableResource(
                ['product' => 123],
                'product',
                'Checkout',
                static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
                true,
            ),
            'Hydration failed for Checkout.product: expected expandable resource string or object, got int.',
        ],
        'expanded resource without id' => [
            static fn (): ?ExpandableResource => Payload::expandableResource(
                ['product' => ['name' => 'Starter']],
                'product',
                'Checkout',
                static fn (array $value): StructuredObject => StructuredObject::fromArray($value),
                true,
            ),
            'Hydration failed for Checkout.id: field is required.',
        ],
        'malformed page item' => [
            static fn (): Page => Payload::page(
                [
                    'items' => ['invalid'],
                    'pagination' => [
                        'total_records' => 1,
                        'total_pages' => 1,
                        'current_page' => 1,
                        'next_page' => null,
                        'prev_page' => null,
                    ],
                ],
                static fn (array $item): mixed => $item,
            ),
            'Hydration failed for Page.items: expected object, got string.',
        ],
        'missing pagination field' => [
            static fn (): ?Pagination => Payload::pagination([
                'pagination' => [
                    'total_records' => 1,
                    'current_page' => 1,
                    'next_page' => null,
                    'prev_page' => null,
                ],
            ], true),
            'Hydration failed for Pagination.total_pages: field is required.',
        ],
        'invalid pagination field type' => [
            static fn (): ?Pagination => Payload::pagination([
                'pagination' => [
                    'total_records' => '1',
                    'total_pages' => 1,
                    'current_page' => 1,
                    'next_page' => null,
                    'prev_page' => null,
                ],
            ], true),
            'Hydration failed for Pagination.total_records: expected int, got string.',
        ],
    ];
}
