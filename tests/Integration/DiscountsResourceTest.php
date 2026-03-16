<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Integration;

use Antoniadisio\Creem\Dto\Discount\CreateDiscountRequest;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\DiscountDuration;
use Antoniadisio\Creem\Enum\DiscountStatus;
use Antoniadisio\Creem\Enum\DiscountType;
use Antoniadisio\Creem\Resource\DiscountsResource;
use Antoniadisio\Creem\Tests\IntegrationTestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test('discounts resource retrieves creates and deletes discounts', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('discount.json')),
        MockResponse::make($this->responseFixture('discount.json')),
        MockResponse::make($this->responseFixture('discount.json', ['id' => 'dis_fixture_welcome'])),
        MockResponse::make($this->responseFixture('discount_deleted.json')),
    ]);
    $resource = new DiscountsResource($this->connector($mockClient));

    $discount = $resource->get('dis_fixture_active');

    expect($discount->id)->toBe('dis_fixture_active')
        ->and($discount->status)->toBe(DiscountStatus::Active)
        ->and($discount->percentage)->toBeNull()
        ->and($discount->expiryDate)->toBeNull();
    $this->assertRequest($mockClient, Method::GET, '/v1/discounts', ['discount_id' => 'dis_fixture_active']);

    $byCode = $resource->getByCode('WELCOME10_FIXTURE');

    expect($byCode->code)->toBe('WELCOME10_FIXTURE');
    $this->assertRequest($mockClient, Method::GET, '/v1/discounts', ['discount_code' => 'WELCOME10_FIXTURE']);

    $created = $resource->create(
        new CreateDiscountRequest(
            'Launch Fixture',
            DiscountType::Fixed,
            DiscountDuration::Once,
            ['prod_fixture_catalog'],
            amount: 1000,
            currency: CurrencyCode::USD,
        ),
        'idem-discount-create',
    );

    expect($created->id)->toBe('dis_fixture_welcome');
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/discounts',
        [],
        [
            'name' => 'Launch Fixture',
            'type' => 'fixed',
            'amount' => 1000,
            'currency' => 'USD',
            'duration' => 'once',
            'applies_to_products' => ['prod_fixture_catalog'],
        ],
        ['Idempotency-Key' => 'idem-discount-create'],
    );

    $deleted = $resource->delete('dis_fixture_active', 'idem-discount-delete');

    expect($deleted->status)->toBe(DiscountStatus::Deleted)
        ->and($deleted->code)->toBe('WELCOME10_FIXTURE');
    $this->assertRequest($mockClient, Method::DELETE, '/v1/discounts/dis_fixture_active/delete', [], null, ['Idempotency-Key' => 'idem-discount-delete']);
});

test('discounts resource normalizes delete identifiers before endpoint resolution', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('discount_deleted.json')),
    ]);
    $resource = new DiscountsResource($this->connector($mockClient));

    $resource->delete('  dis_fixture_active  ');
    $this->assertRequest($mockClient, Method::DELETE, '/v1/discounts/dis_fixture_active/delete');
});
