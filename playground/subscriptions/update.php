<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Dto\Subscription\UpdateSubscriptionRequest;
use Antoniadisio\Creem\Dto\Subscription\UpsertSubscriptionItem;
use Antoniadisio\Creem\Enum\SubscriptionUpdateBehavior;
use Playground\Support\Playground;

$request = static function (array $values): UpdateSubscriptionRequest {
    $productId = Playground::nullableString(
        Playground::value($values, 'subscriptions.update.productId'),
    );
    $unitsValue = Playground::value($values, 'subscriptions.update.units');
    $updateBehaviorValue = Playground::value($values, 'subscriptions.update.updateBehavior');

    return new UpdateSubscriptionRequest(
        [
            new UpsertSubscriptionItem(
                id: Playground::stringValue(
                    Playground::value($values, 'shared.subscriptionItemId'),
                    'shared.subscriptionItemId',
                ),
                productId: $productId,
                priceId: Playground::stringValue(
                    Playground::value($values, 'shared.priceId'),
                    'shared.priceId',
                ),
                units: $unitsValue === null ? null : Playground::intValue($unitsValue, 'subscriptions.update.units'),
            ),
        ],
        $updateBehaviorValue === null ? null : Playground::enumValue(
            SubscriptionUpdateBehavior::class,
            $updateBehaviorValue,
            'subscriptions.update.updateBehavior',
        ),
    );
};

return [
    'resource' => 'subscriptions',
    'action' => 'update',
    'operation_mode' => 'write',
    'sdk_call' => '$client->subscriptions()->update($subscriptionId, new UpdateSubscriptionRequest(...), $idempotencyKey)',
    'http_method' => 'POST',
    'path' => '/v1/subscriptions/{subscriptionId}',
    'fixtures' => 'subscription.json',
    'required_values' => [
        'shared.apiKey',
        'shared.subscriptionId',
        'shared.subscriptionItemId',
        'shared.priceId',
    ],
    'defaults' => [
        'subscriptions' => [
            'update' => [
                'productId' => null,
                'units' => 2,
                'updateBehavior' => null,
            ],
        ],
    ],
    'inputs' => [
        Playground::field('shared.subscriptionId', 'Subscription ID', 'string'),
        Playground::field('shared.subscriptionItemId', 'Subscription item ID', 'string'),
        Playground::field('shared.priceId', 'Price ID', 'string'),
        Playground::field('subscriptions.update.productId', 'Product ID override', 'nullable-string', nullable: true),
        Playground::field('subscriptions.update.units', 'Units', 'nullable-int', nullable: true),
        Playground::field('subscriptions.update.updateBehavior', 'Update behavior', 'enum', nullable: true, enum: SubscriptionUpdateBehavior::class),
        Playground::field('subscriptions.update.idempotencyKey', 'Idempotency key', 'string'),
    ],
    'idempotency_key_path' => 'subscriptions.update.idempotencyKey',
    'persist_outputs' => [
        Playground::persist('shared.subscriptionId', 'id'),
        Playground::persist('shared.subscriptionItemId', 'items.0.id'),
        Playground::persist('shared.priceId', 'items.0.priceId'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'subscriptionId' => Playground::value($values, 'shared.subscriptionId'),
        'subscriptionItemId' => Playground::value($values, 'shared.subscriptionItemId'),
        'priceId' => Playground::value($values, 'shared.priceId'),
        'productId' => Playground::value($values, 'subscriptions.update.productId'),
        'units' => Playground::value($values, 'subscriptions.update.units'),
        'updateBehavior' => Playground::value($values, 'subscriptions.update.updateBehavior'),
        'idempotencyKey' => Playground::value($values, 'subscriptions.update.idempotencyKey'),
    ],
    'build_request_payload' => static fn (array $values): array => $request($values)->toArray(),
    'run' => static fn (Client $client, array $values) => $client->subscriptions()->update(
        Playground::stringValue(
            Playground::value($values, 'shared.subscriptionId'),
            'shared.subscriptionId',
        ),
        $request($values),
        Playground::stringValue(
            Playground::value($values, 'subscriptions.update.idempotencyKey'),
            'subscriptions.update.idempotencyKey',
        ),
    ),
];
