<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Dto\Checkout\CheckoutCustomerInput;
use Antoniadisio\Creem\Dto\Checkout\CreateCheckoutRequest;
use Playground\Support\Playground;

$optionalValue = static function (array $values, string $path): ?string {
    $value = Playground::value($values, $path);

    if (! is_string($value)) {
        return null;
    }

    $value = trim($value);

    if ($value === '' || str_starts_with($value, 'REPLACE_WITH_')) {
        return null;
    }

    return $value;
};

$metadata = static function (array $values): ?array {
    $metadata = Playground::value($values, 'checkouts.create.metadata');

    if ($metadata === null || $metadata === []) {
        return null;
    }

    if (! is_array($metadata)) {
        throw new RuntimeException('Expected [checkouts.create.metadata] to be an array or null.');
    }

    return $metadata;
};

$customer = static function (array $values) use ($optionalValue): ?CheckoutCustomerInput {
    $customerId = $optionalValue($values, 'shared.customerId');
    $customerEmail = $optionalValue($values, 'shared.customerEmail');

    if ($customerId !== null) {
        return new CheckoutCustomerInput($customerId, null);
    }

    if ($customerEmail !== null) {
        return new CheckoutCustomerInput(null, $customerEmail);
    }

    return null;
};

$request = static function (array $values) use ($customer, $metadata, $optionalValue): CreateCheckoutRequest {
    $units = Playground::value($values, 'checkouts.create.units');

    return new CreateCheckoutRequest(
        Playground::stringValue(
            Playground::value($values, 'shared.productId'),
            'shared.productId',
        ),
        requestId: Playground::nullableString(
            Playground::value($values, 'checkouts.create.requestId'),
        ),
        units: $units === null ? null : Playground::intValue($units, 'checkouts.create.units'),
        discountCode: Playground::nullableString(
            Playground::value($values, 'checkouts.create.discountCode'),
        ),
        customer: $customer($values),
        successUrl: $optionalValue($values, 'checkouts.create.successUrl'),
        metadata: $metadata($values),
    );
};

return [
    'resource' => 'checkouts',
    'action' => 'create',
    'operation_mode' => 'write',
    'sdk_call' => '$client->checkouts()->create(new CreateCheckoutRequest(...), $idempotencyKey)',
    'http_method' => 'POST',
    'path' => '/v1/checkouts',
    'fixtures' => 'checkout_create.json',
    'required_values' => [
        'shared.apiKey',
        'shared.productId',
    ],
    'defaults' => [
        'checkouts' => [
            'create' => [
                'requestId' => null,
                'units' => 1,
                'discountCode' => null,
                'successUrl' => 'https://merchant.example.test/playground/checkout/success',
                'metadata' => [],
            ],
        ],
    ],
    'inputs' => [
        Playground::field('shared.productId', 'Product ID', 'string'),
        Playground::field('checkouts.create.requestId', 'Request ID', 'nullable-string', nullable: true),
        Playground::field('checkouts.create.units', 'Units', 'nullable-int', nullable: true),
        Playground::field('checkouts.create.discountCode', 'Discount code', 'nullable-string', nullable: true),
        Playground::field('shared.customerId', 'Customer ID', 'nullable-string', nullable: true),
        Playground::field('shared.customerEmail', 'Customer email', 'nullable-string', nullable: true),
        Playground::field('checkouts.create.successUrl', 'Success URL', 'nullable-string', nullable: true),
        Playground::field('checkouts.create.metadata', 'Metadata JSON', 'json', nullable: true),
        Playground::field('checkouts.create.idempotencyKey', 'Idempotency key', 'string'),
    ],
    'idempotency_key_path' => 'checkouts.create.idempotencyKey',
    'persist_outputs' => [
        Playground::persist('shared.checkoutId', 'id'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'productId' => Playground::value($values, 'shared.productId'),
        'requestId' => Playground::value($values, 'checkouts.create.requestId'),
        'units' => Playground::value($values, 'checkouts.create.units'),
        'discountCode' => Playground::value($values, 'checkouts.create.discountCode'),
        'customerId' => Playground::value($values, 'shared.customerId'),
        'customerEmail' => Playground::value($values, 'shared.customerEmail'),
        'successUrl' => Playground::value($values, 'checkouts.create.successUrl'),
        'metadata' => Playground::value($values, 'checkouts.create.metadata'),
        'idempotencyKey' => Playground::value($values, 'checkouts.create.idempotencyKey'),
    ],
    'build_request_payload' => static fn (array $values): array => $request($values)->toArray(),
    'run' => static fn (Client $client, array $values) => $client->checkouts()->create(
        $request($values),
        Playground::stringValue(
            Playground::value($values, 'checkouts.create.idempotencyKey'),
            'checkouts.create.idempotencyKey',
        ),
    ),
];
