<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Config;
use Creem\Dto\Checkout\CreateCheckoutRequest;
use Creem\Dto\Common\StructuredObject;
use Creem\Dto\Customer\CreateCustomerBillingPortalLinkRequest;
use Creem\Dto\Customer\Customer;
use Creem\Dto\Customer\ListCustomersRequest;
use Creem\Dto\Discount\CreateDiscountRequest;
use Creem\Dto\License\ActivateLicenseRequest;
use Creem\Dto\License\DeactivateLicenseRequest;
use Creem\Dto\License\ValidateLicenseRequest;
use Creem\Dto\Product\CreateProductRequest;
use Creem\Dto\Product\Product;
use Creem\Dto\Product\SearchProductsRequest;
use Creem\Dto\Stats\GetStatsSummaryRequest;
use Creem\Dto\Subscription\CancelSubscriptionRequest;
use Creem\Dto\Subscription\UpdateSubscriptionRequest;
use Creem\Dto\Subscription\UpgradeSubscriptionRequest;
use Creem\Dto\Transaction\SearchTransactionsRequest;
use Creem\Dto\Transaction\Transaction;
use Creem\Internal\Http\CreemConnector;
use Creem\Resource\CheckoutsResource;
use Creem\Resource\CustomersResource;
use Creem\Resource\DiscountsResource;
use Creem\Resource\LicensesResource;
use Creem\Resource\ProductsResource;
use Creem\Resource\StatsResource;
use Creem\Resource\SubscriptionsResource;
use Creem\Resource\TransactionsResource;
use JsonException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

use function json_decode;
use function parse_str;
use function parse_url;

final class ResourcesTest extends TestCase
{
    public function test_products_resource_builds_requests_and_hydrates_responses(): void
    {
        $mockClient = new MockClient([
            MockResponse::make($this->productPayload()),
            MockResponse::make($this->productPayload(['id' => 'prod_456', 'name' => 'Enterprise'])),
            MockResponse::make($this->productPagePayload()),
        ]);
        $resource = new ProductsResource($this->connector($mockClient));

        $product = $resource->get('prod_123');
        self::assertSame('prod_123', $product->id);
        $feature = $product->features->get(0);
        self::assertInstanceOf(StructuredObject::class, $feature);
        self::assertSame('feat_1', $feature->get('id'));
        $this->assertRequest($mockClient, Method::GET, '/v1/products', ['product_id' => 'prod_123']);

        $created = $resource->create(new CreateProductRequest('Enterprise', 4900, 'usd', 'one_time', description: 'Scale plan'));
        self::assertSame('prod_456', $created->id);
        $this->assertRequest(
            $mockClient,
            Method::POST,
            '/v1/products',
            [],
            ['name' => 'Enterprise', 'description' => 'Scale plan', 'price' => 4900, 'currency' => 'usd', 'billing_type' => 'one_time', 'custom_fields' => [], 'custom_field' => []],
        );

        $page = $resource->search(new SearchProductsRequest(2, 50));
        self::assertSame(1, $page->count());
        self::assertSame(2, $page->pagination?->currentPage);
        self::assertNull($page->pagination?->nextPage);
        $item = $page->get(0);
        self::assertInstanceOf(Product::class, $item);
        self::assertSame('prod_123', $item->id);
        $this->assertRequest($mockClient, Method::GET, '/v1/products/search', ['page_number' => '2', 'page_size' => '50']);
    }

    public function test_customers_resource_supports_listing_retrieval_and_billing_links(): void
    {
        $mockClient = new MockClient([
            MockResponse::make($this->customerPagePayload()),
            MockResponse::make($this->customerPayload()),
            MockResponse::make($this->customerPayload(['id' => 'cus_email', 'email' => 'billing@example.com'])),
            MockResponse::make(['customer_portal_link' => 'https://billing.creem.io/session']),
        ]);
        $resource = new CustomersResource($this->connector($mockClient));

        $page = $resource->list(new ListCustomersRequest(1, 20));
        self::assertSame(1, $page->count());
        self::assertInstanceOf(Customer::class, $page->get(0));
        $this->assertRequest($mockClient, Method::GET, '/v1/customers/list', ['page_number' => '1', 'page_size' => '20']);

        $customer = $resource->get('cus_123');
        self::assertSame('cus_123', $customer->id);
        $this->assertRequest($mockClient, Method::GET, '/v1/customers', ['customer_id' => 'cus_123']);

        $customerByEmail = $resource->findByEmail('billing@example.com');
        self::assertSame('billing@example.com', $customerByEmail->email);
        $this->assertRequest($mockClient, Method::GET, '/v1/customers', ['email' => 'billing@example.com']);

        $links = $resource->createBillingPortalLink(new CreateCustomerBillingPortalLinkRequest('cus_123'));
        self::assertSame('https://billing.creem.io/session', $links->customerPortalLink);
        $this->assertRequest($mockClient, Method::POST, '/v1/customers/billing', [], ['customer_id' => 'cus_123']);
    }

    public function test_subscriptions_resource_maps_each_action_endpoint(): void
    {
        $mockClient = new MockClient([
            MockResponse::make($this->subscriptionPayload()),
            MockResponse::make($this->subscriptionPayload(['status' => 'canceled'])),
            MockResponse::make($this->subscriptionPayload(['status' => 'active', 'items' => [['id' => 'item_2', 'units' => 4]]])),
            MockResponse::make($this->subscriptionPayload(['status' => 'active', 'product' => 'prod_999'])),
            MockResponse::make($this->subscriptionPayload(['status' => 'paused'])),
            MockResponse::make($this->subscriptionPayload(['status' => 'active'])),
        ]);
        $resource = new SubscriptionsResource($this->connector($mockClient));

        $subscription = $resource->get('sub_123');
        self::assertSame('prod_123', $subscription->product?->id());
        self::assertFalse($subscription->customer?->isExpanded() ?? true);
        $this->assertRequest($mockClient, Method::GET, '/v1/subscriptions', ['subscription_id' => 'sub_123']);

        $canceled = $resource->cancel('sub_123', new CancelSubscriptionRequest('immediately', 'now'));
        self::assertSame('canceled', $canceled->status);
        $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_123/cancel', [], ['mode' => 'immediately', 'onExecute' => 'now']);

        $updated = $resource->update('sub_123', new UpdateSubscriptionRequest([['product_id' => 'prod_123', 'units' => 4]], 'prorate'));
        self::assertSame(1, $updated->items->count());
        $this->assertRequest(
            $mockClient,
            Method::POST,
            '/v1/subscriptions/sub_123',
            [],
            ['items' => [['product_id' => 'prod_123', 'units' => 4]], 'update_behavior' => 'prorate'],
        );

        $upgraded = $resource->upgrade('sub_123', new UpgradeSubscriptionRequest('prod_999', 'immediate'));
        self::assertSame('prod_999', $upgraded->product?->id());
        $this->assertRequest(
            $mockClient,
            Method::POST,
            '/v1/subscriptions/sub_123/upgrade',
            [],
            ['product_id' => 'prod_999', 'update_behavior' => 'immediate'],
        );

        $paused = $resource->pause('sub_123');
        self::assertSame('paused', $paused->status);
        $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_123/pause');

        $resumed = $resource->resume('sub_123');
        self::assertSame('active', $resumed->status);
        $this->assertRequest($mockClient, Method::POST, '/v1/subscriptions/sub_123/resume');
    }

    public function test_checkouts_resource_handles_get_and_create(): void
    {
        $mockClient = new MockClient([
            MockResponse::make($this->checkoutPayload()),
            MockResponse::make($this->checkoutPayload(['id' => 'chk_456'])),
        ]);
        $resource = new CheckoutsResource($this->connector($mockClient));

        $checkout = $resource->get('chk_123');
        self::assertSame('chk_123', $checkout->id);
        self::assertTrue($checkout->product?->isExpanded());
        $this->assertRequest($mockClient, Method::GET, '/v1/checkouts', ['checkout_id' => 'chk_123']);

        $created = $resource->create(new CreateCheckoutRequest('prod_123', requestId: 'req_1', units: 2, successUrl: 'https://example.com/success'));
        self::assertSame('chk_456', $created->id);
        $this->assertRequest(
            $mockClient,
            Method::POST,
            '/v1/checkouts',
            [],
            ['request_id' => 'req_1', 'product_id' => 'prod_123', 'units' => 2, 'custom_fields' => [], 'custom_field' => [], 'success_url' => 'https://example.com/success'],
        );
    }

    public function test_licenses_resource_maps_activation_validation_and_deactivation(): void
    {
        $mockClient = new MockClient([
            MockResponse::make($this->licensePayload()),
            MockResponse::make($this->licensePayload(['status' => 'inactive'])),
            MockResponse::make($this->licensePayload(['activation' => 1])),
        ]);
        $resource = new LicensesResource($this->connector($mockClient));

        $activated = $resource->activate(new ActivateLicenseRequest('lic_key', 'macbook'));
        self::assertSame('lic_123', $activated->id);
        self::assertSame('ins_123', $activated->instance?->get('id'));
        $this->assertRequest($mockClient, Method::POST, '/v1/licenses/activate', [], ['key' => 'lic_key', 'instance_name' => 'macbook']);

        $deactivated = $resource->deactivate(new DeactivateLicenseRequest('lic_key', 'ins_123'));
        self::assertSame('inactive', $deactivated->status);
        $this->assertRequest($mockClient, Method::POST, '/v1/licenses/deactivate', [], ['key' => 'lic_key', 'instance_id' => 'ins_123']);

        $validated = $resource->validate(new ValidateLicenseRequest('lic_key', 'ins_123'));
        self::assertSame(1, $validated->activation);
        $this->assertRequest($mockClient, Method::POST, '/v1/licenses/validate', [], ['key' => 'lic_key', 'instance_id' => 'ins_123']);
    }

    public function test_discounts_resource_normalizes_lookup_and_delete_operations(): void
    {
        $mockClient = new MockClient([
            MockResponse::make($this->discountPayload()),
            MockResponse::make($this->discountPayload(['code' => 'WELCOME10'])),
            MockResponse::make($this->discountPayload(['id' => 'disc_456'])),
            MockResponse::make($this->discountPayload(['status' => 'deleted'])),
        ]);
        $resource = new DiscountsResource($this->connector($mockClient));

        $discount = $resource->get('disc_123');
        self::assertSame('disc_123', $discount->id);
        $this->assertRequest($mockClient, Method::GET, '/v1/discounts', ['discount_id' => 'disc_123']);

        $byCode = $resource->getByCode('WELCOME10');
        self::assertSame('WELCOME10', $byCode->code);
        $this->assertRequest($mockClient, Method::GET, '/v1/discounts', ['discount_code' => 'WELCOME10']);

        $created = $resource->create(new CreateDiscountRequest('Launch', 'fixed', 'once', ['prod_123'], amount: 1000));
        self::assertSame('disc_456', $created->id);
        $this->assertRequest(
            $mockClient,
            Method::POST,
            '/v1/discounts',
            [],
            ['name' => 'Launch', 'type' => 'fixed', 'amount' => 1000, 'duration' => 'once', 'applies_to_products' => ['prod_123']],
        );

        $deleted = $resource->delete('disc_123');
        self::assertSame('deleted', $deleted->status);
        $this->assertRequest($mockClient, Method::DELETE, '/v1/discounts/disc_123/delete');
    }

    public function test_transactions_resource_supports_get_and_search(): void
    {
        $mockClient = new MockClient([
            MockResponse::make($this->transactionPayload()),
            MockResponse::make($this->transactionPagePayload()),
        ]);
        $resource = new TransactionsResource($this->connector($mockClient));

        $transaction = $resource->get('txn_123');
        self::assertSame('txn_123', $transaction->id);
        $this->assertRequest($mockClient, Method::GET, '/v1/transactions', ['transaction_id' => 'txn_123']);

        $page = $resource->search(new SearchTransactionsRequest(customerId: 'cus_123', pageNumber: 3, pageSize: 25));
        self::assertSame(1, $page->count());
        self::assertSame(3, $page->pagination?->currentPage);
        self::assertNull($page->pagination?->nextPage);
        self::assertInstanceOf(Transaction::class, $page->get(0));
        $this->assertRequest(
            $mockClient,
            Method::GET,
            '/v1/transactions/search',
            ['customer_id' => 'cus_123', 'page_number' => '3', 'page_size' => '25'],
        );
    }

    public function test_stats_resource_returns_typed_summary_data(): void
    {
        $mockClient = new MockClient([
            MockResponse::make([
                'totals' => [
                    'totalProducts' => 2,
                    'totalRevenue' => 12000,
                ],
                'periods' => [
                    [
                        'timestamp' => 1700000000,
                        'grossRevenue' => 12000,
                        'netRevenue' => 11500,
                    ],
                ],
            ]),
        ]);
        $resource = new StatsResource($this->connector($mockClient));

        $summary = $resource->summary(new GetStatsSummaryRequest('usd', 1700000000, 1701000000, 'day'));
        self::assertSame(2, $summary->totals?->get('totalProducts'));
        self::assertSame(1, $summary->periods->count());
        $this->assertRequest(
            $mockClient,
            Method::GET,
            '/v1/stats/summary',
            ['startDate' => '1700000000', 'endDate' => '1701000000', 'interval' => 'day', 'currency' => 'usd'],
        );
    }

    private function connector(MockClient $mockClient): CreemConnector
    {
        return (new CreemConnector(new Config('sk_test_123')))->withMockClient($mockClient);
    }

    /**
     * @param  array<string, string>  $expectedQuery
     * @param  array<string, mixed>|null  $expectedJson
     */
    private function assertRequest(
        MockClient $mockClient,
        Method $expectedMethod,
        string $expectedPath,
        array $expectedQuery = [],
        ?array $expectedJson = null,
    ): void {
        $pendingRequest = $mockClient->getLastPendingRequest();

        self::assertNotNull($pendingRequest);
        self::assertSame($expectedMethod, $pendingRequest->getMethod());

        $psrRequest = $pendingRequest->createPsrRequest();

        self::assertSame($expectedPath, $this->path($psrRequest));
        self::assertSame($expectedQuery, $this->query($psrRequest));

        if ($expectedJson === null) {
            return;
        }

        self::assertSame($expectedJson, $this->jsonBody($psrRequest));
    }

    /**
     * @return array<string, string>
     */
    private function query(RequestInterface $request): array
    {
        $query = parse_url((string) $request->getUri(), PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            return [];
        }

        parse_str($query, $params);

        /** @var array<string, string> $params */
        return $params;
    }

    private function path(RequestInterface $request): string
    {
        return $request->getUri()->getPath();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function jsonBody(RequestInterface $request): array
    {
        /** @var array<string, mixed> */
        return json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productPayload(array $overrides = []): array
    {
        return [
            'id' => 'prod_123',
            'mode' => 'test',
            'object' => 'product',
            'name' => 'Starter',
            'description' => 'Starter plan',
            'features' => [
                ['id' => 'feat_1', 'description' => 'Feature'],
            ],
            'price' => 1900,
            'currency' => 'usd',
            'billing_type' => 'recurring',
            'billing_period' => 'month',
            'status' => 'active',
            'created_at' => '2026-01-01T00:00:00Z',
            'updated_at' => '2026-01-02T00:00:00Z',
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productPagePayload(): array
    {
        return [
            'items' => [$this->productPayload()],
            'pagination' => [
                'total_records' => 1,
                'total_pages' => 1,
                'current_page' => 2,
                'next_page' => null,
                'prev_page' => 1,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function customerPayload(array $overrides = []): array
    {
        return [
            'id' => 'cus_123',
            'mode' => 'test',
            'object' => 'customer',
            'email' => 'hello@example.com',
            'name' => 'Taylor',
            'country' => 'US',
            'created_at' => '2026-01-01T00:00:00Z',
            'updated_at' => '2026-01-02T00:00:00Z',
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function customerPagePayload(): array
    {
        return [
            'items' => [$this->customerPayload()],
            'pagination' => [
                'total_records' => 1,
                'total_pages' => 1,
                'current_page' => 1,
                'next_page' => null,
                'prev_page' => null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function subscriptionPayload(array $overrides = []): array
    {
        return [
            'id' => 'sub_123',
            'mode' => 'test',
            'object' => 'subscription',
            'product' => ['id' => 'prod_123', 'name' => 'Starter'],
            'customer' => 'cus_123',
            'items' => [
                ['id' => 'item_1', 'product_id' => 'prod_123', 'units' => 2],
            ],
            'collection_method' => 'charge_automatically',
            'status' => 'active',
            'last_transaction_id' => 'txn_123',
            'last_transaction' => ['id' => 'txn_123', 'amount' => 1900],
            'current_period_start_date' => '2026-01-01T00:00:00Z',
            'current_period_end_date' => '2026-02-01T00:00:00Z',
            'created_at' => '2026-01-01T00:00:00Z',
            'updated_at' => '2026-01-02T00:00:00Z',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function checkoutPayload(array $overrides = []): array
    {
        return [
            'id' => 'chk_123',
            'mode' => 'test',
            'object' => 'checkout',
            'status' => 'open',
            'request_id' => 'req_1',
            'product' => ['id' => 'prod_123', 'name' => 'Starter'],
            'units' => 2,
            'order' => ['id' => 'ord_123', 'amount' => 3800],
            'subscription' => 'sub_123',
            'customer' => 'cus_123',
            'custom_fields' => [
                ['key' => 'company', 'value' => 'Creem'],
            ],
            'checkout_url' => 'https://checkout.creem.io/session',
            'success_url' => 'https://example.com/success',
            'feature' => [],
            'metadata' => ['source' => 'sdk-test'],
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function licensePayload(array $overrides = []): array
    {
        return [
            'id' => 'lic_123',
            'mode' => 'test',
            'object' => 'license',
            'status' => 'active',
            'key' => 'lic_key',
            'activation' => 2,
            'activation_limit' => 3,
            'expires_at' => '2027-01-01T00:00:00Z',
            'created_at' => '2026-01-01T00:00:00Z',
            'instance' => ['id' => 'ins_123', 'name' => 'macbook'],
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function discountPayload(array $overrides = []): array
    {
        return [
            'id' => 'disc_123',
            'mode' => 'test',
            'object' => 'discount',
            'status' => 'active',
            'name' => 'Launch',
            'code' => 'LAUNCH',
            'type' => 'fixed',
            'amount' => 1000,
            'currency' => 'usd',
            'duration' => 'once',
            'applies_to_products' => ['prod_123'],
            'redeem_count' => 1,
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function transactionPayload(array $overrides = []): array
    {
        return [
            'id' => 'txn_123',
            'mode' => 'test',
            'object' => 'transaction',
            'amount' => 1900,
            'amount_paid' => 1900,
            'discount_amount' => 0,
            'currency' => 'usd',
            'type' => 'payment',
            'status' => 'succeeded',
            'order' => 'ord_123',
            'subscription' => 'sub_123',
            'customer' => 'cus_123',
            'created_at' => 1700000000,
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transactionPagePayload(): array
    {
        return [
            'items' => [$this->transactionPayload()],
            'pagination' => [
                'total_records' => 1,
                'total_pages' => 1,
                'current_page' => 3,
                'next_page' => null,
                'prev_page' => 2,
            ],
        ];
    }
}
