<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Dto\Subscription\UpgradeSubscriptionRequest;
use Antoniadisio\Creem\Enum\SubscriptionUpdateBehavior;
use Playground\Support\Playground;

$request = static function (array $values): UpgradeSubscriptionRequest {
    $updateBehaviorValue = Playground::value($values, 'subscriptions.upgrade.updateBehavior');

    return new UpgradeSubscriptionRequest(
        Playground::stringValue(
            Playground::value($values, 'shared.productId'),
            'shared.productId',
        ),
        $updateBehaviorValue === null ? null : Playground::enumValue(
            SubscriptionUpdateBehavior::class,
            $updateBehaviorValue,
            'subscriptions.upgrade.updateBehavior',
        ),
    );
};

return [
    'resource' => 'subscriptions',
    'action' => 'upgrade',
    'operation_mode' => 'write',
    'sdk_call' => '$client->subscriptions()->upgrade($subscriptionId, new UpgradeSubscriptionRequest(...), $idempotencyKey)',
    'http_method' => 'POST',
    'path' => '/v1/subscriptions/{subscriptionId}/upgrade',
    'fixtures' => 'subscription.json',
    'required_values' => [
        'shared.apiKey',
        'shared.subscriptionId',
        'shared.productId',
    ],
    'defaults' => [
        'subscriptions' => [
            'upgrade' => [
                'updateBehavior' => 'proration-charge-immediately',
            ],
        ],
    ],
    'inputs' => [
        Playground::field('shared.subscriptionId', 'Subscription ID', 'string'),
        Playground::field('shared.productId', 'Product ID', 'string'),
        Playground::field('subscriptions.upgrade.updateBehavior', 'Update behavior', 'enum', nullable: true, enum: SubscriptionUpdateBehavior::class),
        Playground::field('subscriptions.upgrade.idempotencyKey', 'Idempotency key', 'string'),
    ],
    'idempotency_key_path' => 'subscriptions.upgrade.idempotencyKey',
    'persist_outputs' => [
        Playground::persist('shared.subscriptionId', 'id'),
        Playground::persist('shared.subscriptionItemId', 'items.0.id'),
        Playground::persist('shared.priceId', 'items.0.priceId'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'subscriptionId' => Playground::value($values, 'shared.subscriptionId'),
        'productId' => Playground::value($values, 'shared.productId'),
        'updateBehavior' => Playground::value($values, 'subscriptions.upgrade.updateBehavior'),
        'idempotencyKey' => Playground::value($values, 'subscriptions.upgrade.idempotencyKey'),
    ],
    'build_request_payload' => static fn (array $values): array => $request($values)->toArray(),
    'run' => static fn (Client $client, array $values) => $client->subscriptions()->upgrade(
        Playground::stringValue(
            Playground::value($values, 'shared.subscriptionId'),
            'shared.subscriptionId',
        ),
        $request($values),
        Playground::stringValue(
            Playground::value($values, 'subscriptions.upgrade.idempotencyKey'),
            'subscriptions.upgrade.idempotencyKey',
        ),
    ),
];
