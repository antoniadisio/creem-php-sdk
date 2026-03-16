<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Playground\Support\Playground;

return [
    'resource' => 'subscriptions',
    'action' => 'pause',
    'operation_mode' => 'write',
    'sdk_call' => '$client->subscriptions()->pause($subscriptionId, $idempotencyKey)',
    'http_method' => 'POST',
    'path' => '/v1/subscriptions/{subscriptionId}/pause',
    'fixtures' => 'subscription.json',
    'required_values' => [
        'shared.apiKey',
        'shared.subscriptionId',
    ],
    'defaults' => [],
    'inputs' => [
        Playground::field('shared.subscriptionId', 'Subscription ID', 'string'),
        Playground::field('subscriptions.pause.idempotencyKey', 'Idempotency key', 'string'),
    ],
    'idempotency_key_path' => 'subscriptions.pause.idempotencyKey',
    'persist_outputs' => [
        Playground::persist('shared.subscriptionId', 'id'),
        Playground::persist('shared.subscriptionItemId', 'items.0.id'),
        Playground::persist('shared.priceId', 'items.0.priceId'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'subscriptionId' => Playground::value($values, 'shared.subscriptionId'),
        'idempotencyKey' => Playground::value($values, 'subscriptions.pause.idempotencyKey'),
    ],
    'build_request_payload' => static fn (array $values): null => null,
    'run' => static fn (Client $client, array $values) => $client->subscriptions()->pause(
        Playground::stringValue(
            Playground::value($values, 'shared.subscriptionId'),
            'shared.subscriptionId',
        ),
        Playground::stringValue(
            Playground::value($values, 'subscriptions.pause.idempotencyKey'),
            'subscriptions.pause.idempotencyKey',
        ),
    ),
];
