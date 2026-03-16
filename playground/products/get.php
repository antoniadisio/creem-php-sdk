<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Playground\Support\Playground;

return [
    'resource' => 'products',
    'action' => 'get',
    'operation_mode' => 'read',
    'sdk_call' => '$client->products()->get($productId)',
    'http_method' => 'GET',
    'path' => '/v1/products',
    'fixtures' => 'product.json',
    'required_values' => [
        'shared.apiKey',
        'shared.productId',
    ],
    'defaults' => [],
    'inputs' => [
        Playground::field('shared.productId', 'Product ID', 'string'),
    ],
    'idempotency_key_path' => null,
    'persist_outputs' => [
        Playground::persist('shared.productId', 'id'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'productId' => Playground::value($values, 'shared.productId'),
    ],
    'build_request_payload' => static fn (array $values): null => null,
    'run' => static fn (Client $client, array $values) => $client->products()->get(
        Playground::stringValue(
            Playground::value($values, 'shared.productId'),
            'shared.productId',
        ),
    ),
];
