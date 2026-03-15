<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Integration;

use Antoniadisio\Creem\Dto\Customer\CreateCustomerBillingPortalLinkRequest;
use Antoniadisio\Creem\Dto\Customer\Customer;
use Antoniadisio\Creem\Dto\Customer\ListCustomersRequest;
use Antoniadisio\Creem\Enum\ApiMode;
use Antoniadisio\Creem\Resource\CustomersResource;
use Antoniadisio\Creem\Tests\IntegrationTestCase;
use DateTimeImmutable;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test('customers resource lists retrieves and finds customers by email', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('customer_page.json')),
        MockResponse::make($this->responseFixture('customer.json')),
        MockResponse::make($this->responseFixture('customer.json', ['id' => 'cust_fixture_billing', 'email' => 'billing.fixture@example.test'])),
        MockResponse::make($this->responseFixture('customer_links.json')),
    ]);
    $resource = new CustomersResource($this->connector($mockClient));

    $page = $resource->list(new ListCustomersRequest(1, 20));

    expect($page->count())->toBe(1)
        ->and($page->get(0))->toBeInstanceOf(Customer::class);
    $this->assertRequest($mockClient, Method::GET, '/v1/customers/list', ['page_number' => '1', 'page_size' => '20']);

    $customer = $resource->get('cust_fixture_primary');

    expect($customer->id)->toBe('cust_fixture_primary')
        ->and($customer->mode)->toBe(ApiMode::Test)
        ->and($customer->createdAt)->toBeInstanceOf(DateTimeImmutable::class);
    $this->assertRequest($mockClient, Method::GET, '/v1/customers', ['customer_id' => 'cust_fixture_primary']);

    $customerByEmail = $resource->findByEmail('billing.fixture@example.test');

    expect($customerByEmail->email)->toBe('billing.fixture@example.test');
    $this->assertRequest($mockClient, Method::GET, '/v1/customers', ['email' => 'billing.fixture@example.test']);

    $links = $resource->createBillingPortalLink(new CreateCustomerBillingPortalLinkRequest('cust_fixture_primary'), 'idem-customer-links');

    expect($links->customerPortalLink)->toBe('https://creem.io/test/customers/cust_fixture_primary/portal');
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/customers/billing',
        [],
        ['customer_id' => 'cust_fixture_primary'],
        ['Idempotency-Key' => 'idem-customer-links'],
    );
});

test('customers resource omits query parameters when list request is omitted', function (): void {
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
