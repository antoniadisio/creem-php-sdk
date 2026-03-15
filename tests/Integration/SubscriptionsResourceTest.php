<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Integration;

use Antoniadisio\Creem\Dto\Common\ExpandableResource;
use Antoniadisio\Creem\Dto\Subscription\CancelSubscriptionRequest;
use Antoniadisio\Creem\Dto\Subscription\SubscriptionItem;
use Antoniadisio\Creem\Dto\Subscription\UpdateSubscriptionRequest;
use Antoniadisio\Creem\Dto\Subscription\UpgradeSubscriptionRequest;
use Antoniadisio\Creem\Dto\Subscription\UpsertSubscriptionItem;
use Antoniadisio\Creem\Enum\SubscriptionCancellationAction;
use Antoniadisio\Creem\Enum\SubscriptionCancellationMode;
use Antoniadisio\Creem\Enum\SubscriptionCollectionMethod;
use Antoniadisio\Creem\Enum\SubscriptionStatus;
use Antoniadisio\Creem\Enum\SubscriptionUpdateBehavior;
use Antoniadisio\Creem\Enum\TransactionStatus;
use Antoniadisio\Creem\Resource\SubscriptionsResource;
use Antoniadisio\Creem\Tests\IntegrationTestCase;
use InvalidArgumentException;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test('subscriptions resource gets mutates and resumes subscriptions', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('subscription.json')),
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'canceled'])),
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'active', 'items' => [[
            'id' => 'sitem_fixture_updated',
            'mode' => 'test',
            'object' => 'subscription_item',
            'product_id' => 'prod_fixture_subscription',
            'price_id' => 'pprice_fixture_monthly',
            'units' => 4,
            'created_at' => '2026-03-07T06:50:41.467Z',
            'updated_at' => '2026-03-07T06:50:46.748Z',
        ]]])),
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'active', 'product' => 'prod_fixture_growth'])),
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'paused'])),
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'active'])),
    ]);
    $resource = new SubscriptionsResource($this->connector($mockClient));

    $subscription = $resource->get('sub_fixture_active');

    expect($subscription->product)->toBeInstanceOf(ExpandableResource::class)
        ->and($subscription->product?->id())->toBe('prod_fixture_subscription')
        ->and($subscription->product?->isExpanded())->toBeTrue()
        ->and($subscription->customer)->toBeInstanceOf(ExpandableResource::class)
        ->and($subscription->customer?->isExpanded())->toBeTrue()
        ->and($subscription->customer?->id())->toBe('cust_fixture_primary')
        ->and($subscription->collectionMethod)->toBe(SubscriptionCollectionMethod::ChargeAutomatically)
        ->and($subscription->items[0]->object)->toBe('subscription_item')
        ->and($subscription->lastTransaction?->status)->toBe(TransactionStatus::Paid)
        ->and($subscription->lastTransaction?->customer)->toBeNull()
        ->and($subscription->lastTransactionDate?->format(DATE_ATOM))->toBe('2026-03-07T06:50:38+00:00')
        ->and($subscription->discount)->toBeNull()
        ->and($subscription->status)->toBe(SubscriptionStatus::Active);
    $this->assertRequest($mockClient, Method::GET, '/v1/subscriptions', ['subscription_id' => 'sub_fixture_active']);

    $canceled = $resource->cancel(
        'sub_fixture_active',
        new CancelSubscriptionRequest(SubscriptionCancellationMode::Immediate, SubscriptionCancellationAction::Cancel),
        'idem-subscription-cancel',
    );

    expect($canceled->status)->toBe(SubscriptionStatus::Canceled);
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/subscriptions/sub_fixture_active/cancel',
        [],
        ['mode' => 'immediate', 'onExecute' => 'cancel'],
        ['Idempotency-Key' => 'idem-subscription-cancel'],
    );

    $updated = $resource->update(
        'sub_fixture_active',
        new UpdateSubscriptionRequest(
            [new UpsertSubscriptionItem(productId: 'prod_fixture_subscription', units: 4)],
            SubscriptionUpdateBehavior::ProrationCharge,
        ),
        'idem-subscription-update',
    );

    expect($updated->items)->toHaveCount(1)
        ->and($updated->items[0])->toBeInstanceOf(SubscriptionItem::class);
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/subscriptions/sub_fixture_active',
        [],
        ['items' => [['product_id' => 'prod_fixture_subscription', 'units' => 4]], 'update_behavior' => 'proration-charge'],
        ['Idempotency-Key' => 'idem-subscription-update'],
    );

    $upgraded = $resource->upgrade(
        'sub_fixture_active',
        new UpgradeSubscriptionRequest('prod_fixture_growth', SubscriptionUpdateBehavior::ProrationChargeImmediately),
        'idem-subscription-upgrade',
    );

    expect($upgraded->product)->toBeInstanceOf(ExpandableResource::class)
        ->and($upgraded->product?->id())->toBe('prod_fixture_growth');
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/subscriptions/sub_fixture_active/upgrade',
        [],
        ['product_id' => 'prod_fixture_growth', 'update_behavior' => 'proration-charge-immediately'],
        ['Idempotency-Key' => 'idem-subscription-upgrade'],
    );

    $paused = $resource->pause('sub_fixture_active', 'idem-subscription-pause');

    expect($paused->status)->toBe(SubscriptionStatus::Paused);
    $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_fixture_active/pause', [], null, ['Idempotency-Key' => 'idem-subscription-pause']);

    $resumed = $resource->resume('sub_fixture_active', 'idem-subscription-resume');

    expect($resumed->status)->toBe(SubscriptionStatus::Active);
    $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_fixture_active/resume', [], null, ['Idempotency-Key' => 'idem-subscription-resume']);
});

test('subscriptions resource sends an empty payload when cancel request is omitted', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'canceled'])),
    ]);
    $resource = new SubscriptionsResource($this->connector($mockClient));

    $subscription = $resource->cancel('sub_fixture_active', idempotencyKey: 'idem-subscription-cancel-default');

    expect($subscription->status)->toBe(SubscriptionStatus::Canceled);
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/subscriptions/sub_fixture_active/cancel',
        [],
        [],
        ['Idempotency-Key' => 'idem-subscription-cancel-default'],
    );
});

test('subscriptions resource normalizes mutating identifiers before endpoint resolution', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'canceled'])),
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'active'])),
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'active'])),
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'paused'])),
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'active'])),
    ]);
    $resource = new SubscriptionsResource($this->connector($mockClient));

    $resource->cancel(
        '  sub_fixture_active  ',
        new CancelSubscriptionRequest(SubscriptionCancellationMode::Immediate, SubscriptionCancellationAction::Cancel),
    );
    $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_fixture_active/cancel', [], ['mode' => 'immediate', 'onExecute' => 'cancel']);

    $resource->update(
        '  sub_fixture_active  ',
        new UpdateSubscriptionRequest([new UpsertSubscriptionItem(productId: 'prod_fixture_subscription', units: 1)]),
    );
    $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_fixture_active', [], ['items' => [['product_id' => 'prod_fixture_subscription', 'units' => 1]]]);

    $resource->upgrade('  sub_fixture_active  ', new UpgradeSubscriptionRequest('prod_fixture_growth'));
    $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_fixture_active/upgrade', [], ['product_id' => 'prod_fixture_growth']);

    $resource->pause('  sub_fixture_active  ');
    $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_fixture_active/pause');

    $resource->resume('  sub_fixture_active  ');
    $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_fixture_active/resume');
});

foreach (invalidSubscriptionMutatingIdentifiers() as $dataset => [$method, $identifier, $arguments, $message]) {
    test("subscriptions resource rejects invalid mutating identifiers ({$dataset})", function () use ($method, $identifier, $arguments, $message): void {
        /** @var IntegrationTestCase $this */
        $resource = new SubscriptionsResource($this->connector(new MockClient));

        expect(static fn (): mixed => $resource->{$method}($identifier, ...$arguments))
            ->toThrow(InvalidArgumentException::class, $message);
    });
}

/**
 * @return array<string, array{0: string, 1: string, 2: array<int, mixed>, 3: string}>
 */
function invalidSubscriptionMutatingIdentifiers(): array
{
    return [
        'cancel path traversal' => [
            'cancel',
            'sub_123/cancel',
            [new CancelSubscriptionRequest(SubscriptionCancellationMode::Immediate, SubscriptionCancellationAction::Cancel)],
            'The subscription ID cannot contain reserved URI characters or control characters.',
        ],
        'update query injection' => [
            'update',
            'sub_123?force=true',
            [new UpdateSubscriptionRequest([new UpsertSubscriptionItem(productId: 'prod_123', units: 1)])],
            'The subscription ID cannot contain reserved URI characters or control characters.',
        ],
        'upgrade fragment injection' => [
            'upgrade',
            'sub_123#fragment',
            [new UpgradeSubscriptionRequest('prod_999')],
            'The subscription ID cannot contain reserved URI characters or control characters.',
        ],
        'pause percent encoding' => [
            'pause',
            'sub%2F123',
            [],
            'The subscription ID cannot contain reserved URI characters or control characters.',
        ],
        'resume unsupported punctuation' => [
            'resume',
            'sub:123',
            [],
            'The subscription ID contains unsupported characters. Allowed characters are letters, numbers, ".", "_", and "-".',
        ],
        'cancel single dot segment' => [
            'cancel',
            '.',
            [new CancelSubscriptionRequest(SubscriptionCancellationMode::Immediate, SubscriptionCancellationAction::Cancel)],
            'The subscription ID cannot be "." or "..".',
        ],
        'update double dot segment' => [
            'update',
            '..',
            [new UpdateSubscriptionRequest([new UpsertSubscriptionItem(productId: 'prod_123', units: 1)])],
            'The subscription ID cannot be "." or "..".',
        ],
        'upgrade single dot segment' => [
            'upgrade',
            '.',
            [new UpgradeSubscriptionRequest('prod_999')],
            'The subscription ID cannot be "." or "..".',
        ],
        'pause double dot segment' => [
            'pause',
            '..',
            [],
            'The subscription ID cannot be "." or "..".',
        ],
        'resume single dot segment' => [
            'resume',
            '.',
            [],
            'The subscription ID cannot be "." or "..".',
        ],
    ];
}
