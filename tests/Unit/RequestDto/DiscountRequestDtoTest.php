<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Dto\Discount\CreateDiscountRequest;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\DiscountDuration;
use Antoniadisio\Creem\Enum\DiscountType;
use DateTimeImmutable;
use InvalidArgumentException;

test('discount request dtos serialize enums and rfc3339 expiry dates', function (): void {
    $request = new CreateDiscountRequest(
        'Launch',
        DiscountType::Fixed,
        DiscountDuration::Repeating,
        ['prod_123', 'prod_456'],
        code: 'LAUNCH20',
        amount: 2000,
        currency: CurrencyCode::EUR,
        expiryDate: new DateTimeImmutable('2024-12-31T23:59:59Z'),
        maxRedemptions: 100,
        durationInMonths: 6,
    );

    expect($request->toArray())->toBe([
        'name' => 'Launch',
        'code' => 'LAUNCH20',
        'type' => 'fixed',
        'amount' => 2000,
        'currency' => 'EUR',
        'expiry_date' => '2024-12-31T23:59:59+00:00',
        'max_redemptions' => 100,
        'duration' => 'repeating',
        'duration_in_months' => 6,
        'applies_to_products' => ['prod_123', 'prod_456'],
    ]);
});

foreach (invalidDiscountRequestInputs() as $dataset => [$factory, $message]) {
    test("discount request dtos reject invalid input ({$dataset})", function () use ($factory, $message): void {
        expect($factory)->toThrow(InvalidArgumentException::class, $message);
    });
}

/**
 * @return array<string, array{0: callable(): CreateDiscountRequest, 1: string}>
 */
function invalidDiscountRequestInputs(): array
{
    return [
        'non-positive fixed amount' => [
            static fn (): CreateDiscountRequest => new CreateDiscountRequest(
                'Launch',
                DiscountType::Fixed,
                DiscountDuration::Once,
                ['prod_123'],
                amount: 0,
                currency: CurrencyCode::USD,
            ),
            'The fixed discount amount must be greater than zero.',
        ],
        'percentage above range' => [
            static fn (): CreateDiscountRequest => new CreateDiscountRequest(
                'Launch',
                DiscountType::Percentage,
                DiscountDuration::Once,
                ['prod_123'],
                percentage: 101,
            ),
            'The percentage discount value must be between 1 and 100.',
        ],
        'blank applies to product id' => [
            static fn (): CreateDiscountRequest => new CreateDiscountRequest(
                'Launch',
                DiscountType::Fixed,
                DiscountDuration::Once,
                ['prod_123', '   '],
                amount: 100,
                currency: CurrencyCode::USD,
            ),
            'Discount product ID at index 1 cannot be blank.',
        ],
    ];
}
