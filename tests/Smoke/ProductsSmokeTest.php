<?php

declare(strict_types=1);

namespace Creem\Tests\Smoke;

use Creem\Dto\Product\Product;
use Creem\Dto\Product\SearchProductsRequest;
use Creem\Tests\SmokeTestCase;

test('smoke product search returns a typed page', function (): void {
    /** @var SmokeTestCase $this */
    $page = $this->smokeClient()->products()->search(new SearchProductsRequest(pageSize: 1));

    $this->assertTypedSmokePage($page, Product::class);
});

test('smoke product retrieval returns a typed product when a product id is configured', function (): void {
    /** @var SmokeTestCase $this */
    $productId = $this->requireOptionalSmokeValue('CREEM_TEST_PRODUCT_ID', 'products()->get()');
    $product = $this->smokeClient()->products()->get($productId);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->id)->toBe($productId);
});
