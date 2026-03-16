<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Playground\Support\Playground;

return [
    'resource' => 'subscriptions',
    'action' => 'get',
    'operation_mode' => 'read',
    'sdk_call' => '$client->subscriptions()->get($subscriptionId)',
    'http_method' => 'GET',
    'path' => '/v1/subscriptions',
    'fixtures' => 'subscription.json',
    'required_values' => [
        'shared.apiKey',
        'shared.subscriptionId',
    ],
    'defaults' => [],
    'inputs' => [
        Playground::field('shared.subscriptionId', 'Subscription ID', 'string'),
    ],
    'idempotency_key_path' => null,
    'persist_outputs' => [
        Playground::persist('shared.subscriptionId', 'id'),
        Playground::persist('shared.subscriptionItemId', 'items.0.id'),
        Playground::persist('shared.priceId', 'items.0.priceId'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'subscriptionId' => Playground::value($values, 'shared.subscriptionId'),
    ],
    'build_request_payload' => static fn (array $values): null => null,
    'run' => static fn (Client $client, array $values) => $client->subscriptions()->get(
        Playground::stringValue(
            Playground::value($values, 'shared.subscriptionId'),
            'shared.subscriptionId',
        ),
    ),
];
