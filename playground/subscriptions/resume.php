<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Playground\Support\Playground;

return [
    'resource' => 'subscriptions',
    'action' => 'resume',
    'operation_mode' => 'write',
    'sdk_call' => '$client->subscriptions()->resume($subscriptionId, $idempotencyKey)',
    'http_method' => 'POST',
    'path' => '/v1/subscriptions/{subscriptionId}/resume',
    'fixtures' => 'subscription.json',
    'required_values' => [
        'shared.apiKey',
        'shared.subscriptionId',
    ],
    'defaults' => [],
    'inputs' => [
        Playground::field('shared.subscriptionId', 'Subscription ID', 'string'),
        Playground::field('subscriptions.resume.idempotencyKey', 'Idempotency key', 'string'),
    ],
    'idempotency_key_path' => 'subscriptions.resume.idempotencyKey',
    'persist_outputs' => [
        Playground::persist('shared.subscriptionId', 'id'),
        Playground::persist('shared.subscriptionItemId', 'items.0.id'),
        Playground::persist('shared.priceId', 'items.0.priceId'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'subscriptionId' => Playground::value($values, 'shared.subscriptionId'),
        'idempotencyKey' => Playground::value($values, 'subscriptions.resume.idempotencyKey'),
    ],
    'build_request_payload' => static fn (array $values): null => null,
    'run' => static fn (Client $client, array $values) => $client->subscriptions()->resume(
        Playground::stringValue(
            Playground::value($values, 'shared.subscriptionId'),
            'shared.subscriptionId',
        ),
        Playground::stringValue(
            Playground::value($values, 'subscriptions.resume.idempotencyKey'),
            'subscriptions.resume.idempotencyKey',
        ),
    ),
];
