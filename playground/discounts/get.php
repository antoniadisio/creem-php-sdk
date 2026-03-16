<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Playground\Support\Playground;

return [
    'resource' => 'discounts',
    'action' => 'get',
    'operation_mode' => 'read',
    'sdk_call' => '$client->discounts()->get($discountId)',
    'http_method' => 'GET',
    'path' => '/v1/discounts',
    'fixtures' => 'discount.json',
    'required_values' => [
        'shared.apiKey',
        'shared.discountId',
    ],
    'defaults' => [],
    'inputs' => [
        Playground::field('shared.discountId', 'Discount ID', 'string'),
    ],
    'idempotency_key_path' => null,
    'persist_outputs' => [
        Playground::persist('shared.discountId', 'id'),
        Playground::persist('shared.discountCode', 'code'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'discountId' => Playground::value($values, 'shared.discountId'),
    ],
    'build_request_payload' => static fn (array $values): null => null,
    'run' => static fn (Client $client, array $values) => $client->discounts()->get(
        Playground::stringValue(
            Playground::value($values, 'shared.discountId'),
            'shared.discountId',
        ),
    ),
];
