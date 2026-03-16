<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Dto\Subscription\CancelSubscriptionRequest;
use Antoniadisio\Creem\Enum\SubscriptionCancellationAction;
use Antoniadisio\Creem\Enum\SubscriptionCancellationMode;
use Playground\Support\Playground;

$request = static function (array $values): CancelSubscriptionRequest {
    $modeValue = Playground::value($values, 'subscriptions.cancel.mode');
    $onExecuteValue = Playground::value($values, 'subscriptions.cancel.onExecute');

    return new CancelSubscriptionRequest(
        $modeValue === null ? null : Playground::enumValue(
            SubscriptionCancellationMode::class,
            $modeValue,
            'subscriptions.cancel.mode',
        ),
        $onExecuteValue === null ? null : Playground::enumValue(
            SubscriptionCancellationAction::class,
            $onExecuteValue,
            'subscriptions.cancel.onExecute',
        ),
    );
};

return [
    'resource' => 'subscriptions',
    'action' => 'cancel',
    'operation_mode' => 'write',
    'sdk_call' => '$client->subscriptions()->cancel($subscriptionId, new CancelSubscriptionRequest(...), $idempotencyKey)',
    'http_method' => 'POST',
    'path' => '/v1/subscriptions/{subscriptionId}/cancel',
    'fixtures' => 'subscription.json',
    'required_values' => [
        'shared.apiKey',
        'shared.subscriptionId',
    ],
    'defaults' => [
        'subscriptions' => [
            'cancel' => [
                'mode' => 'immediate',
                'onExecute' => 'cancel',
            ],
        ],
    ],
    'inputs' => [
        Playground::field('shared.subscriptionId', 'Subscription ID', 'string'),
        Playground::field('subscriptions.cancel.mode', 'Cancellation mode', 'enum', nullable: true, enum: SubscriptionCancellationMode::class),
        Playground::field('subscriptions.cancel.onExecute', 'On execute action', 'enum', nullable: true, enum: SubscriptionCancellationAction::class),
        Playground::field('subscriptions.cancel.idempotencyKey', 'Idempotency key', 'string'),
    ],
    'idempotency_key_path' => 'subscriptions.cancel.idempotencyKey',
    'persist_outputs' => [
        Playground::persist('shared.subscriptionId', 'id'),
        Playground::persist('shared.subscriptionItemId', 'items.0.id'),
        Playground::persist('shared.priceId', 'items.0.priceId'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'subscriptionId' => Playground::value($values, 'shared.subscriptionId'),
        'mode' => Playground::value($values, 'subscriptions.cancel.mode'),
        'onExecute' => Playground::value($values, 'subscriptions.cancel.onExecute'),
        'idempotencyKey' => Playground::value($values, 'subscriptions.cancel.idempotencyKey'),
    ],
    'build_request_payload' => static fn (array $values): array => $request($values)->toArray(),
    'run' => static fn (Client $client, array $values) => $client->subscriptions()->cancel(
        Playground::stringValue(
            Playground::value($values, 'shared.subscriptionId'),
            'shared.subscriptionId',
        ),
        $request($values),
        Playground::stringValue(
            Playground::value($values, 'subscriptions.cancel.idempotencyKey'),
            'subscriptions.cancel.idempotencyKey',
        ),
    ),
];
