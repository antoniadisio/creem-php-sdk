<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Integration;

use Antoniadisio\Creem\Dto\Checkout\CreateCheckoutRequest;
use Antoniadisio\Creem\Dto\Common\ExpandableResource;
use Antoniadisio\Creem\Enum\CheckoutStatus;
use Antoniadisio\Creem\Resource\CheckoutsResource;
use Antoniadisio\Creem\Tests\IntegrationTestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test('checkouts resource gets and creates checkouts', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('checkout.json')),
        MockResponse::make($this->responseFixture('checkout_create.json')),
    ]);
    $resource = new CheckoutsResource($this->connector($mockClient));

    $checkout = $resource->get('ch_fixture_pending');

    expect($checkout->id)->toBe('ch_fixture_pending')
        ->and($checkout->status)->toBe(CheckoutStatus::Pending)
        ->and($checkout->product)->toBeInstanceOf(ExpandableResource::class)
        ->and($checkout->product?->isExpanded())->toBeFalse()
        ->and($checkout->product?->id())->toBe('prod_fixture_catalog')
        ->and($checkout->order)->toBeNull()
        ->and($checkout->customFields)->toBe([])
        ->and($checkout->checkoutUrl)->toBeNull()
        ->and($checkout->feature)->toBe([])
        ->and($checkout->metadata)->toBeNull();
    $this->assertRequest($mockClient, Method::GET, '/v1/checkouts', ['checkout_id' => 'ch_fixture_pending']);

    $created = $resource->create(
        new CreateCheckoutRequest('prod_fixture_catalog', requestId: 'req_fixture_checkout_create', units: 2, successUrl: 'https://merchant.example/checkout/success'),
        'idem-checkout-create',
    );

    expect($created->id)->toBe('ch_fixture_created')
        ->and($created->checkoutUrl)->toBe('https://creem.io/test/checkout/prod_fixture_catalog/ch_fixture_created')
        ->and($created->customFields)->toBe([]);
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/checkouts',
        [],
        ['request_id' => 'req_fixture_checkout_create', 'product_id' => 'prod_fixture_catalog', 'units' => 2, 'custom_fields' => [], 'success_url' => 'https://merchant.example/checkout/success'],
        ['Idempotency-Key' => 'idem-checkout-create'],
    );
});
