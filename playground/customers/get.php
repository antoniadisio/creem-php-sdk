<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Playground\Support\Playground;

return [
    'resource' => 'customers',
    'action' => 'get',
    'operation_mode' => 'read',
    'sdk_call' => '$client->customers()->get($customerId)',
    'http_method' => 'GET',
    'path' => '/v1/customers',
    'fixtures' => 'customer.json',
    'required_values' => [
        'shared.apiKey',
        'shared.customerId',
    ],
    'defaults' => [],
    'inputs' => [
        Playground::field('shared.customerId', 'Customer ID', 'string'),
    ],
    'idempotency_key_path' => null,
    'persist_outputs' => [
        Playground::persist('shared.customerId', 'id'),
        Playground::persist('shared.customerEmail', 'email'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'customerId' => Playground::value($values, 'shared.customerId'),
    ],
    'build_request_payload' => static fn (array $values): null => null,
    'run' => static fn (Client $client, array $values) => $client->customers()->get(
        Playground::stringValue(
            Playground::value($values, 'shared.customerId'),
            'shared.customerId',
        ),
    ),
];
