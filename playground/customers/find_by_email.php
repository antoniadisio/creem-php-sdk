<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Playground\Support\Playground;

return [
    'resource' => 'customers',
    'action' => 'find_by_email',
    'operation_mode' => 'read',
    'sdk_call' => '$client->customers()->findByEmail($email)',
    'http_method' => 'GET',
    'path' => '/v1/customers',
    'fixtures' => 'customer.json',
    'required_values' => [
        'shared.apiKey',
        'shared.customerEmail',
    ],
    'defaults' => [],
    'inputs' => [
        Playground::field('shared.customerEmail', 'Customer email', 'string'),
    ],
    'idempotency_key_path' => null,
    'persist_outputs' => [
        Playground::persist('shared.customerId', 'id'),
        Playground::persist('shared.customerEmail', 'email'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'email' => Playground::value($values, 'shared.customerEmail'),
    ],
    'build_request_payload' => static fn (array $values): null => null,
    'run' => static fn (Client $client, array $values) => $client->customers()->findByEmail(
        Playground::stringValue(
            Playground::value($values, 'shared.customerEmail'),
            'shared.customerEmail',
        ),
    ),
];
