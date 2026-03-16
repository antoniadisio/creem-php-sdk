<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Dto\Discount\CreateDiscountRequest;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\DiscountDuration;
use Antoniadisio\Creem\Enum\DiscountType;
use Playground\Support\Playground;

$productIds = static function (array $values): array {
    $primaryProductId = Playground::stringValue(
        Playground::value($values, 'shared.productId'),
        'shared.productId',
    );

    $extraProductIds = Playground::value($values, 'discounts.create.extraProductIds') ?? [];

    if (! is_array($extraProductIds)) {
        throw new RuntimeException('Expected [discounts.create.extraProductIds] to be a list.');
    }

    $resolved = [$primaryProductId];

    foreach ($extraProductIds as $index => $productId) {
        $resolved[] = Playground::stringValue($productId, 'discounts.create.extraProductIds.'.$index);
    }

    return $resolved;
};

$request = static function (array $values) use ($productIds): CreateDiscountRequest {
    $typeValue = Playground::value($values, 'discounts.create.type');
    $durationValue = Playground::value($values, 'discounts.create.duration');
    $amountValue = Playground::value($values, 'discounts.create.amount');
    $percentageValue = Playground::value($values, 'discounts.create.percentage');
    $maxRedemptionsValue = Playground::value($values, 'discounts.create.maxRedemptions');
    $durationInMonthsValue = Playground::value($values, 'discounts.create.durationInMonths');
    $currencyValue = Playground::value($values, 'discounts.create.currency');

    return new CreateDiscountRequest(
        Playground::stringValue(
            Playground::value($values, 'discounts.create.name'),
            'discounts.create.name',
        ),
        Playground::enumValue(
            DiscountType::class,
            $typeValue,
            'discounts.create.type',
        ),
        Playground::enumValue(
            DiscountDuration::class,
            $durationValue,
            'discounts.create.duration',
        ),
        $productIds($values),
        code: Playground::nullableString(
            Playground::value($values, 'discounts.create.code'),
        ),
        amount: $amountValue === null ? null : Playground::intValue($amountValue, 'discounts.create.amount'),
        currency: $currencyValue === null ? null : Playground::enumValue(
            CurrencyCode::class,
            $currencyValue,
            'discounts.create.currency',
        ),
        percentage: $percentageValue === null ? null : Playground::intValue($percentageValue, 'discounts.create.percentage'),
        expiryDate: Playground::nullableDateTime(
            Playground::value($values, 'discounts.create.expiryDate'),
            'discounts.create.expiryDate',
        ),
        maxRedemptions: $maxRedemptionsValue === null ? null : Playground::intValue($maxRedemptionsValue, 'discounts.create.maxRedemptions'),
        durationInMonths: $durationInMonthsValue === null ? null : Playground::intValue($durationInMonthsValue, 'discounts.create.durationInMonths'),
    );
};

return [
    'resource' => 'discounts',
    'action' => 'create',
    'operation_mode' => 'write',
    'sdk_call' => '$client->discounts()->create(new CreateDiscountRequest(...), $idempotencyKey)',
    'http_method' => 'POST',
    'path' => '/v1/discounts',
    'fixtures' => 'discount.json',
    'required_values' => [
        'shared.apiKey',
        'shared.productId',
        'discounts.create.name',
    ],
    'defaults' => [
        'discounts' => [
            'create' => [
                'name' => 'SDK Harness Discount',
                'type' => 'fixed',
                'duration' => 'once',
                'code' => null,
                'amount' => 1000,
                'currency' => 'USD',
                'percentage' => null,
                'expiryDate' => null,
                'maxRedemptions' => null,
                'durationInMonths' => null,
                'extraProductIds' => [],
            ],
        ],
    ],
    'inputs' => [
        Playground::field('shared.productId', 'Primary product ID', 'string'),
        Playground::field('discounts.create.name', 'Discount name', 'string'),
        Playground::field('discounts.create.type', 'Discount type', 'enum', enum: DiscountType::class),
        Playground::field('discounts.create.duration', 'Duration', 'enum', enum: DiscountDuration::class),
        Playground::field('discounts.create.code', 'Discount code', 'nullable-string', nullable: true),
        Playground::field('discounts.create.amount', 'Fixed amount', 'nullable-int', nullable: true),
        Playground::field('discounts.create.currency', 'Currency', 'enum', nullable: true, enum: CurrencyCode::class),
        Playground::field('discounts.create.percentage', 'Percentage', 'nullable-int', nullable: true),
        Playground::field('discounts.create.expiryDate', 'Expiry date', 'nullable-string', nullable: true),
        Playground::field('discounts.create.maxRedemptions', 'Max redemptions', 'nullable-int', nullable: true),
        Playground::field('discounts.create.durationInMonths', 'Duration in months', 'nullable-int', nullable: true),
        Playground::field('discounts.create.extraProductIds', 'Extra product IDs JSON', 'json'),
        Playground::field('discounts.create.idempotencyKey', 'Idempotency key', 'string'),
    ],
    'idempotency_key_path' => 'discounts.create.idempotencyKey',
    'persist_outputs' => [
        Playground::persist('shared.discountId', 'id'),
        Playground::persist('shared.discountCode', 'code'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'name' => Playground::value($values, 'discounts.create.name'),
        'type' => Playground::value($values, 'discounts.create.type'),
        'duration' => Playground::value($values, 'discounts.create.duration'),
        'productId' => Playground::value($values, 'shared.productId'),
        'extraProductIds' => Playground::value($values, 'discounts.create.extraProductIds'),
        'code' => Playground::value($values, 'discounts.create.code'),
        'amount' => Playground::value($values, 'discounts.create.amount'),
        'currency' => Playground::value($values, 'discounts.create.currency'),
        'percentage' => Playground::value($values, 'discounts.create.percentage'),
        'expiryDate' => Playground::value($values, 'discounts.create.expiryDate'),
        'maxRedemptions' => Playground::value($values, 'discounts.create.maxRedemptions'),
        'durationInMonths' => Playground::value($values, 'discounts.create.durationInMonths'),
        'idempotencyKey' => Playground::value($values, 'discounts.create.idempotencyKey'),
    ],
    'build_request_payload' => static fn (array $values): array => $request($values)->toArray(),
    'run' => static fn (Client $client, array $values) => $client->discounts()->create(
        $request($values),
        Playground::stringValue(
            Playground::value($values, 'discounts.create.idempotencyKey'),
            'discounts.create.idempotencyKey',
        ),
    ),
];
