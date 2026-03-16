<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Playground\Support\Playground;

return [
    'resource' => 'discounts',
    'action' => 'delete',
    'operation_mode' => 'write',
    'sdk_call' => '$client->discounts()->delete($discountId, $idempotencyKey)',
    'http_method' => 'DELETE',
    'path' => '/v1/discounts/{discountId}/delete',
    'fixtures' => 'discount_deleted.json',
    'required_values' => [
        'shared.apiKey',
        'shared.discountId',
    ],
    'defaults' => [],
    'inputs' => [
        Playground::field('shared.discountId', 'Discount ID', 'string'),
        Playground::field('discounts.delete.idempotencyKey', 'Idempotency key', 'string'),
    ],
    'idempotency_key_path' => 'discounts.delete.idempotencyKey',
    'persist_outputs' => [
        Playground::persist('shared.discountId', 'id'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'discountId' => Playground::value($values, 'shared.discountId'),
        'idempotencyKey' => Playground::value($values, 'discounts.delete.idempotencyKey'),
    ],
    'build_request_payload' => static fn (array $values): null => null,
    'run' => static fn (Client $client, array $values) => $client->discounts()->delete(
        Playground::stringValue(
            Playground::value($values, 'shared.discountId'),
            'shared.discountId',
        ),
        Playground::stringValue(
            Playground::value($values, 'discounts.delete.idempotencyKey'),
            'discounts.delete.idempotencyKey',
        ),
    ),
];
