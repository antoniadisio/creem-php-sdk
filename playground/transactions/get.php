<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Playground\Support\Playground;

return [
    'resource' => 'transactions',
    'action' => 'get',
    'operation_mode' => 'read',
    'sdk_call' => '$client->transactions()->get($transactionId)',
    'http_method' => 'GET',
    'path' => '/v1/transactions',
    'fixtures' => 'transaction.json',
    'required_values' => [
        'shared.apiKey',
        'shared.transactionId',
    ],
    'defaults' => [],
    'inputs' => [
        Playground::field('shared.transactionId', 'Transaction ID', 'string'),
    ],
    'idempotency_key_path' => null,
    'persist_outputs' => [
        Playground::persist('shared.transactionId', 'id'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'transactionId' => Playground::value($values, 'shared.transactionId'),
    ],
    'build_request_payload' => static fn (array $values): null => null,
    'run' => static fn (Client $client, array $values) => $client->transactions()->get(
        Playground::stringValue(
            Playground::value($values, 'shared.transactionId'),
            'shared.transactionId',
        ),
    ),
];
