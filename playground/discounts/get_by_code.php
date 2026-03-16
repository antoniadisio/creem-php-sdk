<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Playground\Support\Playground;

return [
    'resource' => 'discounts',
    'action' => 'get_by_code',
    'operation_mode' => 'read',
    'sdk_call' => '$client->discounts()->getByCode($discountCode)',
    'http_method' => 'GET',
    'path' => '/v1/discounts',
    'fixtures' => 'discount.json',
    'required_values' => [
        'shared.apiKey',
        'shared.discountCode',
    ],
    'defaults' => [],
    'inputs' => [
        Playground::field('shared.discountCode', 'Discount code', 'string'),
    ],
    'idempotency_key_path' => null,
    'persist_outputs' => [
        Playground::persist('shared.discountId', 'id'),
        Playground::persist('shared.discountCode', 'code'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'discountCode' => Playground::value($values, 'shared.discountCode'),
    ],
    'build_request_payload' => static fn (array $values): null => null,
    'run' => static fn (Client $client, array $values) => $client->discounts()->getByCode(
        Playground::stringValue(
            Playground::value($values, 'shared.discountCode'),
            'shared.discountCode',
        ),
    ),
];
