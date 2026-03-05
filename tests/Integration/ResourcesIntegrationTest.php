<?php

declare(strict_types=1);

namespace Creem\Tests\Integration;

use Creem\Dto\Checkout\CreateCheckoutRequest;
use Creem\Dto\Common\CustomField;
use Creem\Dto\Common\ExpandableResource;
use Creem\Dto\Common\Order;
use Creem\Dto\Common\Pagination;
use Creem\Dto\Common\ProductFeature;
use Creem\Dto\Customer\CreateCustomerBillingPortalLinkRequest;
use Creem\Dto\Customer\Customer;
use Creem\Dto\Customer\ListCustomersRequest;
use Creem\Dto\Discount\CreateDiscountRequest;
use Creem\Dto\License\ActivateLicenseRequest;
use Creem\Dto\License\DeactivateLicenseRequest;
use Creem\Dto\License\LicenseInstance;
use Creem\Dto\License\ValidateLicenseRequest;
use Creem\Dto\Product\CreateProductRequest;
use Creem\Dto\Product\Product;
use Creem\Dto\Product\SearchProductsRequest;
use Creem\Dto\Stats\GetStatsSummaryRequest;
use Creem\Dto\Stats\StatsPeriod;
use Creem\Dto\Stats\StatsTotals;
use Creem\Dto\Subscription\CancelSubscriptionRequest;
use Creem\Dto\Subscription\SubscriptionItem;
use Creem\Dto\Subscription\UpdateSubscriptionRequest;
use Creem\Dto\Subscription\UpgradeSubscriptionRequest;
use Creem\Dto\Subscription\UpsertSubscriptionItem;
use Creem\Dto\Transaction\SearchTransactionsRequest;
use Creem\Dto\Transaction\Transaction;
use Creem\Enum\ApiMode;
use Creem\Enum\BillingPeriod;
use Creem\Enum\BillingType;
use Creem\Enum\CheckoutStatus;
use Creem\Enum\CurrencyCode;
use Creem\Enum\DiscountDuration;
use Creem\Enum\DiscountStatus;
use Creem\Enum\DiscountType;
use Creem\Enum\LicenseStatus;
use Creem\Enum\ProductFeatureType;
use Creem\Enum\StatsInterval;
use Creem\Enum\SubscriptionCancellationAction;
use Creem\Enum\SubscriptionCancellationMode;
use Creem\Enum\SubscriptionStatus;
use Creem\Enum\SubscriptionUpdateBehavior;
use Creem\Enum\TransactionStatus;
use Creem\Resource\CustomersResource;
use Creem\Resource\DiscountsResource;
use Creem\Resource\LicensesResource;
use Creem\Resource\ProductsResource;
use Creem\Resource\StatsResource;
use Creem\Resource\SubscriptionsResource;
use Creem\Resource\TransactionsResource;
use Creem\Tests\IntegrationTestCase;
use Creem\Tests\Support\ResourceBehaviorTestCatalog;
use DateTimeImmutable;
use InvalidArgumentException;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test(ResourceBehaviorTestCatalog::PRODUCTS, function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('product.json')),
        MockResponse::make($this->responseFixture('product.json', ['id' => 'prod_456', 'name' => 'Enterprise'])),
        MockResponse::make($this->responseFixture('product_page.json')),
    ]);
    $resource = new ProductsResource($this->connector($mockClient));

    $product = $resource->get('prod_123');

    expect($product->id)->toBe('prod_123')
        ->and($product->mode)->toBe(ApiMode::Test)
        ->and($product->currency)->toBe(CurrencyCode::USD)
        ->and($product->billingPeriod)->toBe(BillingPeriod::EveryMonth)
        ->and($product->features[0] ?? null)->toBeInstanceOf(ProductFeature::class)
        ->and($product->features[0]->type)->toBe(ProductFeatureType::LicenseKey);
    $this->assertRequest($mockClient, Method::GET, '/v1/products', ['product_id' => 'prod_123']);

    $created = $resource->create(
        new CreateProductRequest('Enterprise', 4900, CurrencyCode::USD, BillingType::OneTime, description: 'Scale plan'),
        'idem-product-create',
    );

    expect($created->id)->toBe('prod_456');
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/products',
        [],
        ['name' => 'Enterprise', 'description' => 'Scale plan', 'price' => 4900, 'currency' => 'USD', 'billing_type' => 'onetime', 'custom_fields' => []],
        ['Idempotency-Key' => 'idem-product-create'],
    );

    $page = $resource->search(new SearchProductsRequest(2, 50));

    expect($page->count())->toBe(1)
        ->and($page->pagination)->toBeInstanceOf(Pagination::class)
        ->and($page->pagination?->currentPage)->toBe(2)
        ->and($page->pagination?->nextPage)->toBeNull()
        ->and($page->get(0))->toBeInstanceOf(Product::class)
        ->and($page->get(0)?->id)->toBe('prod_123')
        ->and($page->get(0)?->currency)->toBe(CurrencyCode::USD);
    $this->assertRequest($mockClient, Method::GET, '/v1/products/search', ['page_number' => '2', 'page_size' => '50']);
});

test('products resource applies empty query defaults when search dto is omitted', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('product_page.json')),
    ]);
    $resource = new ProductsResource($this->connector($mockClient));

    $page = $resource->search();

    expect($page->count())->toBe(1)
        ->and($page->get(0))->toBeInstanceOf(Product::class);
    $this->assertRequest($mockClient, Method::GET, '/v1/products/search');
});

test(ResourceBehaviorTestCatalog::CUSTOMERS, function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('customer_page.json')),
        MockResponse::make($this->responseFixture('customer.json')),
        MockResponse::make($this->responseFixture('customer.json', ['id' => 'cus_email', 'email' => 'billing@example.com'])),
        MockResponse::make($this->responseFixture('customer_links.json')),
    ]);
    $resource = new CustomersResource($this->connector($mockClient));

    $page = $resource->list(new ListCustomersRequest(1, 20));

    expect($page->count())->toBe(1)
        ->and($page->get(0))->toBeInstanceOf(Customer::class);
    $this->assertRequest($mockClient, Method::GET, '/v1/customers/list', ['page_number' => '1', 'page_size' => '20']);

    $customer = $resource->get('cus_123');

    expect($customer->id)->toBe('cus_123')
        ->and($customer->mode)->toBe(ApiMode::Test)
        ->and($customer->createdAt)->toBeInstanceOf(DateTimeImmutable::class);
    $this->assertRequest($mockClient, Method::GET, '/v1/customers', ['customer_id' => 'cus_123']);

    $customerByEmail = $resource->findByEmail('billing@example.com');

    expect($customerByEmail->email)->toBe('billing@example.com');
    $this->assertRequest($mockClient, Method::GET, '/v1/customers', ['email' => 'billing@example.com']);

    $links = $resource->createBillingPortalLink(new CreateCustomerBillingPortalLinkRequest('cus_123'), 'idem-customer-links');

    expect($links->customerPortalLink)->toBe('https://billing.creem.io/session');
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/customers/billing',
        [],
        ['customer_id' => 'cus_123'],
        ['Idempotency-Key' => 'idem-customer-links'],
    );
});

test('customers resource applies empty query defaults when list dto is omitted', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('customer_page.json')),
    ]);
    $resource = new CustomersResource($this->connector($mockClient));

    $page = $resource->list();

    expect($page->count())->toBe(1)
        ->and($page->get(0))->toBeInstanceOf(Customer::class);
    $this->assertRequest($mockClient, Method::GET, '/v1/customers/list');
});

test(ResourceBehaviorTestCatalog::SUBSCRIPTIONS, function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('subscription.json')),
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'canceled'])),
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'active', 'items' => [[
            'id' => 'item_2',
            'mode' => 'test',
            'object' => 'subscription-item',
            'product_id' => 'prod_123',
            'price_id' => 'price_123',
            'units' => 4,
        ]]])),
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'active', 'product' => 'prod_999'])),
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'paused'])),
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'active'])),
    ]);
    $resource = new SubscriptionsResource($this->connector($mockClient));

    $subscription = $resource->get('sub_123');

    expect($subscription->product)->toBeInstanceOf(ExpandableResource::class)
        ->and($subscription->product?->id())->toBe('prod_123')
        ->and($subscription->product?->isExpanded())->toBeTrue()
        ->and($subscription->customer)->toBeInstanceOf(ExpandableResource::class)
        ->and($subscription->customer?->isExpanded())->toBeFalse()
        ->and($subscription->status)->toBe(SubscriptionStatus::Active);
    $this->assertRequest($mockClient, Method::GET, '/v1/subscriptions', ['subscription_id' => 'sub_123']);

    $canceled = $resource->cancel(
        'sub_123',
        new CancelSubscriptionRequest(SubscriptionCancellationMode::Immediate, SubscriptionCancellationAction::Cancel),
        'idem-subscription-cancel',
    );

    expect($canceled->status)->toBe(SubscriptionStatus::Canceled);
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/subscriptions/sub_123/cancel',
        [],
        ['mode' => 'immediate', 'onExecute' => 'cancel'],
        ['Idempotency-Key' => 'idem-subscription-cancel'],
    );

    $updated = $resource->update(
        'sub_123',
        new UpdateSubscriptionRequest(
            [new UpsertSubscriptionItem(productId: 'prod_123', units: 4)],
            SubscriptionUpdateBehavior::ProrationCharge,
        ),
        'idem-subscription-update',
    );

    expect($updated->items)->toHaveCount(1)
        ->and($updated->items[0])->toBeInstanceOf(SubscriptionItem::class);
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/subscriptions/sub_123',
        [],
        ['items' => [['product_id' => 'prod_123', 'units' => 4]], 'update_behavior' => 'proration-charge'],
        ['Idempotency-Key' => 'idem-subscription-update'],
    );

    $upgraded = $resource->upgrade(
        'sub_123',
        new UpgradeSubscriptionRequest('prod_999', SubscriptionUpdateBehavior::ProrationChargeImmediately),
        'idem-subscription-upgrade',
    );

    expect($upgraded->product)->toBeInstanceOf(ExpandableResource::class)
        ->and($upgraded->product?->id())->toBe('prod_999');
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/subscriptions/sub_123/upgrade',
        [],
        ['product_id' => 'prod_999', 'update_behavior' => 'proration-charge-immediately'],
        ['Idempotency-Key' => 'idem-subscription-upgrade'],
    );

    $paused = $resource->pause('sub_123', 'idem-subscription-pause');

    expect($paused->status)->toBe(SubscriptionStatus::Paused);
    $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_123/pause', [], null, ['Idempotency-Key' => 'idem-subscription-pause']);

    $resumed = $resource->resume('sub_123', 'idem-subscription-resume');

    expect($resumed->status)->toBe(SubscriptionStatus::Active);
    $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_123/resume', [], null, ['Idempotency-Key' => 'idem-subscription-resume']);
});

test('subscriptions resource applies empty payload defaults when cancel dto is omitted', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('subscription.json', ['status' => 'canceled'])),
    ]);
    $resource = new SubscriptionsResource($this->connector($mockClient));

    $subscription = $resource->cancel('sub_123', idempotencyKey: 'idem-subscription-cancel-default');

    expect($subscription->status)->toBe(SubscriptionStatus::Canceled);
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/subscriptions/sub_123/cancel',
        [],
        [],
        ['Idempotency-Key' => 'idem-subscription-cancel-default'],
    );
});

test('subscriptions resource normalizes mutating path identifiers before endpoint resolution', function (): void {
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
        '  sub_123  ',
        new CancelSubscriptionRequest(SubscriptionCancellationMode::Immediate, SubscriptionCancellationAction::Cancel),
    );
    $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_123/cancel', [], ['mode' => 'immediate', 'onExecute' => 'cancel']);

    $resource->update(
        '  sub_123  ',
        new UpdateSubscriptionRequest([new UpsertSubscriptionItem(productId: 'prod_123', units: 1)]),
    );
    $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_123', [], ['items' => [['product_id' => 'prod_123', 'units' => 1]]]);

    $resource->upgrade('  sub_123  ', new UpgradeSubscriptionRequest('prod_999'));
    $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_123/upgrade', [], ['product_id' => 'prod_999']);

    $resource->pause('  sub_123  ');
    $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_123/pause');

    $resource->resume('  sub_123  ');
    $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_123/resume');
});

test('subscriptions resource rejects route manipulation identifiers on mutating endpoints', function (): void {
    /** @var IntegrationTestCase $this */
    $resource = new SubscriptionsResource($this->connector(new MockClient));
    $cancelRequest = new CancelSubscriptionRequest(
        SubscriptionCancellationMode::Immediate,
        SubscriptionCancellationAction::Cancel,
    );
    $updateRequest = new UpdateSubscriptionRequest([new UpsertSubscriptionItem(productId: 'prod_123', units: 1)]);
    $upgradeRequest = new UpgradeSubscriptionRequest('prod_999');

    expect(static fn (): \Creem\Dto\Subscription\Subscription => $resource->cancel('sub_123/cancel', $cancelRequest))
        ->toThrow(
            InvalidArgumentException::class,
            'The subscription ID cannot contain reserved URI characters or control characters.',
        );

    expect(static fn (): \Creem\Dto\Subscription\Subscription => $resource->update('sub_123?force=true', $updateRequest))
        ->toThrow(
            InvalidArgumentException::class,
            'The subscription ID cannot contain reserved URI characters or control characters.',
        );

    expect(static fn (): \Creem\Dto\Subscription\Subscription => $resource->upgrade('sub_123#fragment', $upgradeRequest))
        ->toThrow(
            InvalidArgumentException::class,
            'The subscription ID cannot contain reserved URI characters or control characters.',
        );

    expect(static fn (): \Creem\Dto\Subscription\Subscription => $resource->pause('sub%2F123'))
        ->toThrow(
            InvalidArgumentException::class,
            'The subscription ID cannot contain reserved URI characters or control characters.',
        );

    expect(static fn (): \Creem\Dto\Subscription\Subscription => $resource->resume('sub:123'))
        ->toThrow(
            InvalidArgumentException::class,
            'The subscription ID contains unsupported characters. Allowed characters are letters, numbers, ".", "_", and "-".',
        );

    expect(static fn (): \Creem\Dto\Subscription\Subscription => $resource->cancel('.', $cancelRequest))
        ->toThrow(
            InvalidArgumentException::class,
            'The subscription ID cannot be "." or "..".',
        );

    expect(static fn (): \Creem\Dto\Subscription\Subscription => $resource->update('..', $updateRequest))
        ->toThrow(
            InvalidArgumentException::class,
            'The subscription ID cannot be "." or "..".',
        );

    expect(static fn (): \Creem\Dto\Subscription\Subscription => $resource->upgrade('.', $upgradeRequest))
        ->toThrow(
            InvalidArgumentException::class,
            'The subscription ID cannot be "." or "..".',
        );

    expect(static fn (): \Creem\Dto\Subscription\Subscription => $resource->pause('..'))
        ->toThrow(
            InvalidArgumentException::class,
            'The subscription ID cannot be "." or "..".',
        );

    expect(static fn (): \Creem\Dto\Subscription\Subscription => $resource->resume('.'))
        ->toThrow(
            InvalidArgumentException::class,
            'The subscription ID cannot be "." or "..".',
        );
});

test(ResourceBehaviorTestCatalog::CHECKOUTS, function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('checkout.json')),
        MockResponse::make($this->responseFixture('checkout.json', ['id' => 'chk_456'])),
    ]);
    $resource = new \Creem\Resource\CheckoutsResource($this->connector($mockClient));

    $checkout = $resource->get('chk_123');

    expect($checkout->id)->toBe('chk_123')
        ->and($checkout->status)->toBe(CheckoutStatus::Pending)
        ->and($checkout->product)->toBeInstanceOf(ExpandableResource::class)
        ->and($checkout->product?->isExpanded())->toBeTrue()
        ->and($checkout->order)->toBeInstanceOf(Order::class)
        ->and($checkout->customFields[0] ?? null)->toBeInstanceOf(CustomField::class)
        ->and($checkout->feature[0] ?? null)->toBeInstanceOf(ProductFeature::class)
        ->and($checkout->metadata)->toBeArray()
        ->and($checkout->metadata['source'] ?? null)->toBe('sdk-test');
    $this->assertRequest($mockClient, Method::GET, '/v1/checkouts', ['checkout_id' => 'chk_123']);

    $created = $resource->create(
        new CreateCheckoutRequest('prod_123', requestId: 'req_1', units: 2, successUrl: 'https://example.com/success'),
        'idem-checkout-create',
    );

    expect($created->id)->toBe('chk_456');
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/checkouts',
        [],
        ['request_id' => 'req_1', 'product_id' => 'prod_123', 'units' => 2, 'custom_fields' => [], 'success_url' => 'https://example.com/success'],
        ['Idempotency-Key' => 'idem-checkout-create'],
    );
});

test(ResourceBehaviorTestCatalog::LICENSES, function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('license.json')),
        MockResponse::make($this->responseFixture('license.json', ['status' => 'inactive'])),
        MockResponse::make($this->responseFixture('license.json', ['activation' => 1])),
    ]);
    $resource = new LicensesResource($this->connector($mockClient));

    $activated = $resource->activate(new ActivateLicenseRequest('lic_key', 'macbook'), 'idem-license-activate');

    expect($activated->id)->toBe('lic_123')
        ->and($activated->status)->toBe(LicenseStatus::Active)
        ->and($activated->instance)->toBeInstanceOf(LicenseInstance::class)
        ->and($activated->instance?->id)->toBe('ins_123');
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/licenses/activate',
        [],
        ['key' => 'lic_key', 'instance_name' => 'macbook'],
        ['Idempotency-Key' => 'idem-license-activate'],
    );

    $deactivated = $resource->deactivate(new DeactivateLicenseRequest('lic_key', 'ins_123'), 'idem-license-deactivate');

    expect($deactivated->status)->toBe(LicenseStatus::Inactive);
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/licenses/deactivate',
        [],
        ['key' => 'lic_key', 'instance_id' => 'ins_123'],
        ['Idempotency-Key' => 'idem-license-deactivate'],
    );

    $validated = $resource->validate(new ValidateLicenseRequest('lic_key', 'ins_123'), 'idem-license-validate');

    expect($validated->activation)->toBe(1);
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/licenses/validate',
        [],
        ['key' => 'lic_key', 'instance_id' => 'ins_123'],
        ['Idempotency-Key' => 'idem-license-validate'],
    );
});

test(ResourceBehaviorTestCatalog::DISCOUNTS, function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('discount.json')),
        MockResponse::make($this->responseFixture('discount.json', ['code' => 'WELCOME10'])),
        MockResponse::make($this->responseFixture('discount.json', ['id' => 'disc_456'])),
        MockResponse::make($this->responseFixture('discount.json', ['status' => 'expired'])),
    ]);
    $resource = new DiscountsResource($this->connector($mockClient));

    $discount = $resource->get('disc_123');

    expect($discount->id)->toBe('disc_123')
        ->and($discount->status)->toBe(DiscountStatus::Active);
    $this->assertRequest($mockClient, Method::GET, '/v1/discounts', ['discount_id' => 'disc_123']);

    $byCode = $resource->getByCode('WELCOME10');

    expect($byCode->code)->toBe('WELCOME10');
    $this->assertRequest($mockClient, Method::GET, '/v1/discounts', ['discount_code' => 'WELCOME10']);

    $created = $resource->create(
        new CreateDiscountRequest(
            'Launch',
            DiscountType::Fixed,
            DiscountDuration::Once,
            ['prod_123'],
            amount: 1000,
            currency: CurrencyCode::USD,
        ),
        'idem-discount-create',
    );

    expect($created->id)->toBe('disc_456');
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/discounts',
        [],
        [
            'name' => 'Launch',
            'type' => 'fixed',
            'amount' => 1000,
            'currency' => 'USD',
            'duration' => 'once',
            'applies_to_products' => ['prod_123'],
        ],
        ['Idempotency-Key' => 'idem-discount-create'],
    );

    $deleted = $resource->delete('disc_123', 'idem-discount-delete');

    expect($deleted->status)->toBe(DiscountStatus::Expired);
    $this->assertRequest($mockClient, Method::DELETE, '/v1/discounts/disc_123/delete', [], null, ['Idempotency-Key' => 'idem-discount-delete']);
});

test('discounts resource normalizes delete identifiers and rejects route manipulation input', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('discount.json', ['status' => 'expired'])),
    ]);
    $resource = new DiscountsResource($this->connector($mockClient));

    $resource->delete('  disc_123  ');
    $this->assertRequest($mockClient, Method::DELETE, '/v1/discounts/disc_123/delete');

    expect(static fn (): \Creem\Dto\Discount\Discount => $resource->delete('disc_123/delete'))
        ->toThrow(
            InvalidArgumentException::class,
            'The discount ID cannot contain reserved URI characters or control characters.',
        );

    expect(static fn (): \Creem\Dto\Discount\Discount => $resource->delete('.'))
        ->toThrow(
            InvalidArgumentException::class,
            'The discount ID cannot be "." or "..".',
        );

    expect(static fn (): \Creem\Dto\Discount\Discount => $resource->delete('..'))
        ->toThrow(
            InvalidArgumentException::class,
            'The discount ID cannot be "." or "..".',
        );
});

test(ResourceBehaviorTestCatalog::TRANSACTIONS, function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('transaction.json')),
        MockResponse::make($this->responseFixture('transaction_page.json')),
    ]);
    $resource = new TransactionsResource($this->connector($mockClient));

    $transaction = $resource->get('txn_123');

    expect($transaction->id)->toBe('txn_123')
        ->and($transaction->currency)->toBe(CurrencyCode::USD)
        ->and($transaction->status)->toBe(TransactionStatus::Paid);
    $this->assertRequest($mockClient, Method::GET, '/v1/transactions', ['transaction_id' => 'txn_123']);

    $page = $resource->search(new SearchTransactionsRequest(customerId: 'cus_123', pageNumber: 3, pageSize: 25));

    expect($page->count())->toBe(1)
        ->and($page->pagination)->toBeInstanceOf(Pagination::class)
        ->and($page->pagination?->currentPage)->toBe(3)
        ->and($page->pagination?->nextPage)->toBeNull()
        ->and($page->get(0))->toBeInstanceOf(Transaction::class)
        ->and($page->get(0)?->status)->toBe(TransactionStatus::Paid);
    $this->assertRequest(
        $mockClient,
        Method::GET,
        '/v1/transactions/search',
        ['customer_id' => 'cus_123', 'page_number' => '3', 'page_size' => '25'],
    );
});

test('transactions resource applies empty query defaults when search dto is omitted', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('transaction_page.json')),
    ]);
    $resource = new TransactionsResource($this->connector($mockClient));

    $page = $resource->search();

    expect($page->count())->toBe(1)
        ->and($page->get(0))->toBeInstanceOf(Transaction::class);
    $this->assertRequest($mockClient, Method::GET, '/v1/transactions/search');
});

test(ResourceBehaviorTestCatalog::STATS, function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('stats_summary.json')),
    ]);
    $resource = new StatsResource($this->connector($mockClient));

    $summary = $resource->summary(
        new GetStatsSummaryRequest(
            CurrencyCode::USD,
            new DateTimeImmutable('@1700000000'),
            new DateTimeImmutable('@1701000000'),
            StatsInterval::Day,
        ),
    );

    expect($summary->totals)->toBeInstanceOf(StatsTotals::class)
        ->and($summary->periods)->toHaveCount(1)
        ->and($summary->periods[0] ?? null)->toBeInstanceOf(StatsPeriod::class);

    if ($summary->totals instanceof StatsTotals) {
        expect($summary->totals->totalProducts)->toBe(2);
    }

    if (($summary->periods[0] ?? null) instanceof StatsPeriod) {
        expect($summary->periods[0]->timestamp)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($summary->periods[0]->timestamp?->format(DATE_ATOM))->toBe('2023-11-14T22:13:20+00:00');
    }
    $this->assertRequest(
        $mockClient,
        Method::GET,
        '/v1/stats/summary',
        ['startDate' => '1700000000000', 'endDate' => '1701000000000', 'interval' => 'day', 'currency' => 'USD'],
    );
});
