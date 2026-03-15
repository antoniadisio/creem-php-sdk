<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Dto\Subscription\CancelSubscriptionRequest;
use Antoniadisio\Creem\Dto\Subscription\UpdateSubscriptionRequest;
use Antoniadisio\Creem\Dto\Subscription\UpgradeSubscriptionRequest;
use Antoniadisio\Creem\Dto\Subscription\UpsertSubscriptionItem;
use Antoniadisio\Creem\Enum\SubscriptionCancellationAction;
use Antoniadisio\Creem\Enum\SubscriptionCancellationMode;
use Antoniadisio\Creem\Enum\SubscriptionUpdateBehavior;
use InvalidArgumentException;

test('subscription cancel request serializes cancellation settings', function (): void {
    $request = new CancelSubscriptionRequest(
        SubscriptionCancellationMode::Scheduled,
        SubscriptionCancellationAction::Pause,
    );

    expect($request->toArray())->toBe([
        'mode' => 'scheduled',
        'onExecute' => 'pause',
    ]);
});

test('subscription update and upgrade requests keep their payloads distinct', function (): void {
    $updateRequest = new UpdateSubscriptionRequest(
        [
            new UpsertSubscriptionItem(id: 'item_123', productId: 'prod_123', priceId: 'price_123', units: 4),
        ],
        SubscriptionUpdateBehavior::ProrationNone,
    );
    $upgradeRequest = new UpgradeSubscriptionRequest(
        '  prod_999  ',
        SubscriptionUpdateBehavior::ProrationChargeImmediately,
    );

    expect($updateRequest->toArray())->toBe([
        'items' => [
            [
                'id' => 'item_123',
                'product_id' => 'prod_123',
                'price_id' => 'price_123',
                'units' => 4,
            ],
        ],
        'update_behavior' => 'proration-none',
    ])
        ->and($upgradeRequest->toArray())->toBe([
            'product_id' => 'prod_999',
            'update_behavior' => 'proration-charge-immediately',
        ]);
});

test('subscription update requests support price based seat updates with explicit proration behavior', function (): void {
    $request = new UpdateSubscriptionRequest(
        [
            new UpsertSubscriptionItem(
                id: 'sitem_123',
                priceId: 'pprice_123',
                units: 2,
            ),
        ],
        SubscriptionUpdateBehavior::ProrationChargeImmediately,
    );

    expect($request->toArray())->toBe([
        'items' => [
            [
                'id' => 'sitem_123',
                'price_id' => 'pprice_123',
                'units' => 2,
            ],
        ],
        'update_behavior' => 'proration-charge-immediately',
    ]);
});

foreach (invalidSubscriptionRequestInputs() as $dataset => [$factory, $message]) {
    test("subscription request dtos reject invalid input ({$dataset})", function () use ($factory, $message): void {
        expect($factory)->toThrow(InvalidArgumentException::class, $message);
    });
}

/**
 * @return array<string, array{0: callable(): mixed, 1: string}>
 */
function invalidSubscriptionRequestInputs(): array
{
    return [
        'subscription item requires one identifier' => [
            static fn (): UpsertSubscriptionItem => new UpsertSubscriptionItem(units: 2),
            'At least one of subscription item ID, product ID, or price ID must be provided.',
        ],
        'subscription item units must be positive' => [
            static fn (): UpsertSubscriptionItem => new UpsertSubscriptionItem(productId: 'prod_123', units: 0),
            'The subscription item units must be greater than zero.',
        ],
        'blank upgrade product id' => [
            static fn (): UpgradeSubscriptionRequest => new UpgradeSubscriptionRequest('   '),
            'The subscription upgrade product ID cannot be blank.',
        ],
        'invalid update items list' => [
            static fn (): UpdateSubscriptionRequest => new UpdateSubscriptionRequest(
                items: ['bad-item'],
                updateBehavior: SubscriptionUpdateBehavior::ProrationNone,
            ),
            'Subscription item at index 0 must be an instance of Antoniadisio\\Creem\\Dto\\Subscription\\UpsertSubscriptionItem, string given.',
        ],
    ];
}
