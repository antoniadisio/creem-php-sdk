<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Dto\Product\CreateProductRequest;
use Antoniadisio\Creem\Enum\BillingPeriod;
use Antoniadisio\Creem\Enum\BillingType;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\TaxCategory;
use Antoniadisio\Creem\Enum\TaxMode;
use Playground\Support\Playground;

$request = static function (array $values): CreateProductRequest {
    return new CreateProductRequest(
        name: Playground::stringValue(
            Playground::value($values, 'products.create.name'),
            'products.create.name',
        ),
        price: Playground::intValue(
            Playground::value($values, 'products.create.price'),
            'products.create.price',
        ),
        currency: Playground::enumValue(
            CurrencyCode::class,
            Playground::value($values, 'products.create.currency'),
            'products.create.currency',
        ),
        billingType: Playground::enumValue(
            BillingType::class,
            Playground::value($values, 'products.create.billingType'),
            'products.create.billingType',
        ),
        description: Playground::nullableString(
            Playground::value($values, 'products.create.description'),
        ),
        imageUrl: Playground::nullableString(
            Playground::value($values, 'products.create.imageUrl'),
        ),
        billingPeriod: Playground::enumValue(
            BillingPeriod::class,
            Playground::value($values, 'products.create.billingPeriod'),
            'products.create.billingPeriod',
        ),
        taxMode: Playground::enumValue(
            TaxMode::class,
            Playground::value($values, 'products.create.taxMode'),
            'products.create.taxMode',
        ),
        taxCategory: Playground::enumValue(
            TaxCategory::class,
            Playground::value($values, 'products.create.taxCategory'),
            'products.create.taxCategory',
        ),
        defaultSuccessUrl: Playground::nullableString(
            Playground::value($values, 'products.create.defaultSuccessUrl'),
        ),
        customFields: [],
        abandonedCartRecoveryEnabled: Playground::boolValue(
            Playground::value($values, 'products.create.abandonedCartRecoveryEnabled'),
            'products.create.abandonedCartRecoveryEnabled',
        ),
    );
};

return [
    'resource' => 'products',
    'action' => 'create',
    'operation_mode' => 'write',
    'sdk_call' => '$client->products()->create(new CreateProductRequest(...), $idempotencyKey)',
    'http_method' => 'POST',
    'path' => '/v1/products',
    'fixtures' => 'product.json',
    'required_values' => [
        'shared.apiKey',
        'products.create.name',
    ],
    'defaults' => [
        'products' => [
            'create' => [
                'name' => 'SDK Harness Product',
                'description' => 'SDK harness product',
                'imageUrl' => null,
                'price' => 4900,
                'currency' => 'USD',
                'billingType' => 'recurring',
                'billingPeriod' => 'every-month',
                'taxMode' => 'inclusive',
                'taxCategory' => 'saas',
                'defaultSuccessUrl' => 'https://merchant.example.test/playground/success',
                'abandonedCartRecoveryEnabled' => false,
            ],
        ],
    ],
    'inputs' => [
        Playground::field('products.create.name', 'Product name', 'string'),
        Playground::field('products.create.description', 'Description', 'nullable-string', nullable: true),
        Playground::field('products.create.imageUrl', 'Image URL', 'nullable-string', nullable: true),
        Playground::field('products.create.price', 'Price', 'int'),
        Playground::field('products.create.currency', 'Currency', 'enum', enum: CurrencyCode::class),
        Playground::field('products.create.billingType', 'Billing type', 'enum', enum: BillingType::class),
        Playground::field('products.create.billingPeriod', 'Billing period', 'enum', enum: BillingPeriod::class),
        Playground::field('products.create.taxMode', 'Tax mode', 'enum', enum: TaxMode::class),
        Playground::field('products.create.taxCategory', 'Tax category', 'enum', enum: TaxCategory::class),
        Playground::field('products.create.defaultSuccessUrl', 'Default success URL', 'nullable-string', nullable: true),
        Playground::field('products.create.abandonedCartRecoveryEnabled', 'Abandoned cart recovery enabled', 'bool'),
        Playground::field('products.create.idempotencyKey', 'Idempotency key', 'string'),
    ],
    'idempotency_key_path' => 'products.create.idempotencyKey',
    'persist_outputs' => [
        Playground::persist('shared.productId', 'id'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'name' => Playground::value($values, 'products.create.name'),
        'description' => Playground::value($values, 'products.create.description'),
        'imageUrl' => Playground::value($values, 'products.create.imageUrl'),
        'price' => Playground::value($values, 'products.create.price'),
        'currency' => Playground::value($values, 'products.create.currency'),
        'billingType' => Playground::value($values, 'products.create.billingType'),
        'billingPeriod' => Playground::value($values, 'products.create.billingPeriod'),
        'taxMode' => Playground::value($values, 'products.create.taxMode'),
        'taxCategory' => Playground::value($values, 'products.create.taxCategory'),
        'defaultSuccessUrl' => Playground::value($values, 'products.create.defaultSuccessUrl'),
        'abandonedCartRecoveryEnabled' => Playground::value($values, 'products.create.abandonedCartRecoveryEnabled'),
        'idempotencyKey' => Playground::value($values, 'products.create.idempotencyKey'),
    ],
    'build_request_payload' => static fn (array $values): array => $request($values)->toArray(),
    'run' => static fn (Client $client, array $values) => $client->products()->create(
        $request($values),
        Playground::stringValue(
            Playground::value($values, 'products.create.idempotencyKey'),
            'products.create.idempotencyKey',
        ),
    ),
];
