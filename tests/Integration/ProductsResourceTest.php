<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Integration;

use Antoniadisio\Creem\Dto\Common\Pagination;
use Antoniadisio\Creem\Dto\Product\CreateProductRequest;
use Antoniadisio\Creem\Dto\Product\Product;
use Antoniadisio\Creem\Dto\Product\SearchProductsRequest;
use Antoniadisio\Creem\Enum\ApiMode;
use Antoniadisio\Creem\Enum\BillingPeriod;
use Antoniadisio\Creem\Enum\BillingType;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\CustomFieldType;
use Antoniadisio\Creem\Enum\ProductFeatureType;
use Antoniadisio\Creem\Resource\ProductsResource;
use Antoniadisio\Creem\Tests\IntegrationTestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test('products resource gets creates and searches products', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('product.json')),
        MockResponse::make($this->responseFixture('product.json', ['id' => 'prod_fixture_enterprise', 'name' => 'Enterprise Fixture', 'custom_fields' => []])),
        MockResponse::make($this->responseFixture('product_page.json')),
    ]);
    $resource = new ProductsResource($this->connector($mockClient));

    $product = $resource->get('prod_fixture_catalog');

    expect($product->id)->toBe('prod_fixture_catalog')
        ->and($product->mode)->toBe(ApiMode::Test)
        ->and($product->currency)->toBe(CurrencyCode::USD)
        ->and($product->billingPeriod)->toBe(BillingPeriod::EveryMonth)
        ->and($product->createdAt?->format(DATE_ATOM))->toBe('2026-03-07T06:35:41+00:00')
        ->and($product->imageUrl)->toBeNull()
        ->and($product->features)->toHaveCount(1)
        ->and($product->features[0]->id)->toBe('feat_fixture_license_key')
        ->and($product->features[0]->type)->toBe(ProductFeatureType::LicenseKey)
        ->and($product->features[0]->description)->toBe('License Key')
        ->and($product->customFields)->toHaveCount(2)
        ->and($product->customFields[0]->type)->toBe(CustomFieldType::Text)
        ->and($product->customFields[0]->key)->toBe('company_name')
        ->and($product->customFields[0]->text?->value)->toBe('Example Company')
        ->and($product->customFields[1]->type)->toBe(CustomFieldType::Checkbox)
        ->and($product->customFields[1]->checkbox?->value)->toBeTrue()
        ->and($product->defaultSuccessUrl)->toBeNull();
    $this->assertRequest($mockClient, Method::GET, '/v1/products', ['product_id' => 'prod_fixture_catalog']);

    $created = $resource->create(
        new CreateProductRequest('Enterprise', 4900, CurrencyCode::USD, BillingType::OneTime, description: 'Scale plan'),
        'idem-product-create',
    );

    expect($created->id)->toBe('prod_fixture_enterprise');
    $this->assertRequest(
        $mockClient,
        Method::POST,
        '/v1/products',
        [],
        ['name' => 'Enterprise', 'description' => 'Scale plan', 'price' => 4900, 'currency' => 'USD', 'billing_type' => 'onetime', 'custom_fields' => []],
        ['Idempotency-Key' => 'idem-product-create'],
    );

    $page = $resource->search(new SearchProductsRequest(1, 50));

    expect($page->count())->toBe(2)
        ->and($page->pagination)->toBeInstanceOf(Pagination::class)
        ->and($page->pagination?->currentPage)->toBe(1)
        ->and($page->pagination?->nextPage)->toBe(2)
        ->and($page->pagination?->totalPages)->toBe(56)
        ->and($page->get(0))->toBeInstanceOf(Product::class)
        ->and($page->get(0)?->id)->toBe('prod_fixture_catalog')
        ->and($page->get(0)?->currency)->toBe(CurrencyCode::USD)
        ->and($page->get(0)?->features)->toBe([])
        ->and($page->get(0)?->customFields)->toHaveCount(1)
        ->and($page->get(0)?->customFields[0]->type)->toBe(CustomFieldType::Text)
        ->and($page->get(0)?->customFields[0]->key)->toBe('team_name')
        ->and($page->get(1))->toBeInstanceOf(Product::class)
        ->and($page->get(1)?->id)->toBe('prod_fixture_license')
        ->and($page->get(1)?->billingType)->toBe(BillingType::OneTime)
        ->and($page->get(1)?->billingPeriod)->toBe(BillingPeriod::Once)
        ->and($page->get(1)?->features)->toHaveCount(1)
        ->and($page->get(1)?->features[0]->type)->toBe(ProductFeatureType::LicenseKey)
        ->and($page->get(1)?->features[0]->description)->toBe('License Key')
        ->and($page->get(1)?->customFields)->toHaveCount(1)
        ->and($page->get(1)?->customFields[0]->type)->toBe(CustomFieldType::Checkbox)
        ->and($page->get(1)?->customFields[0]->checkbox?->value)->toBeFalse();
    $this->assertRequest($mockClient, Method::GET, '/v1/products/search', ['page_number' => '1', 'page_size' => '50']);
});

test('products resource omits query parameters when search request is omitted', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('product_page.json')),
    ]);
    $resource = new ProductsResource($this->connector($mockClient));

    $page = $resource->search();

    expect($page->count())->toBe(2)
        ->and($page->get(0))->toBeInstanceOf(Product::class)
        ->and($page->get(1))->toBeInstanceOf(Product::class);
    $this->assertRequest($mockClient, Method::GET, '/v1/products/search');
});
