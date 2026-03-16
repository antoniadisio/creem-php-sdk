<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Playground\Support\Playground;

return [
    'resource' => 'checkouts',
    'action' => 'get',
    'operation_mode' => 'read',
    'sdk_call' => '$client->checkouts()->get($checkoutId)',
    'http_method' => 'GET',
    'path' => '/v1/checkouts',
    'fixtures' => 'checkout.json',
    'required_values' => [
        'shared.apiKey',
        'shared.checkoutId',
    ],
    'defaults' => [],
    'inputs' => [
        Playground::field('shared.checkoutId', 'Checkout ID', 'string'),
    ],
    'idempotency_key_path' => null,
    'persist_outputs' => [
        Playground::persist('shared.checkoutId', 'id'),
        Playground::persist('shared.customerId', 'customer.id'),
        Playground::persist('shared.subscriptionId', 'subscription.id'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'checkoutId' => Playground::value($values, 'shared.checkoutId'),
    ],
    'build_request_payload' => static fn (array $values): null => null,
    'run' => static fn (Client $client, array $values) => $client->checkouts()->get(
        Playground::stringValue(
            Playground::value($values, 'shared.checkoutId'),
            'shared.checkoutId',
        ),
    ),
];
