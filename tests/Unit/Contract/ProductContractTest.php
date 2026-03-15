<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Tests\TestCase;

test('product response contract exposes custom fields in the stable schema', function (): void {
    /** @var TestCase $this */
    $customFields = $this->openApiSpec()->objectAtPath('components.schemas.ProductEntity.properties.custom_fields');
    $items = $customFields['items'] ?? null;

    $this->assertIsArray($items);

    expect($customFields['type'] ?? null)->toBe('array')
        ->and($items['$ref'] ?? null)->toBe('#/components/schemas/CustomField');
});

test('product response fixtures cover custom fields for retrieve and search flows', function (): void {
    /** @var TestCase $this */
    $product = $this->fixture('product.json');
    $page = $this->fixture('product_page.json');
    $productCustomFields = $product['custom_fields'] ?? null;
    $pageItems = $page['items'] ?? null;

    $this->assertIsArray($productCustomFields);
    $this->assertIsArray($pageItems);
    $this->assertArrayHasKey(0, $pageItems);
    $this->assertArrayHasKey(1, $pageItems);

    /** @var array<string, mixed> $firstItem */
    $firstItem = $pageItems[0];
    /** @var array<string, mixed> $secondItem */
    $secondItem = $pageItems[1];
    $firstItemCustomFields = $firstItem['custom_fields'] ?? null;
    $secondItemCustomFields = $secondItem['custom_fields'] ?? null;

    $this->assertIsArray($firstItemCustomFields);
    $this->assertIsArray($secondItemCustomFields);

    expect($productCustomFields)->toHaveCount(2)
        ->and($firstItemCustomFields)->toHaveCount(1)
        ->and($secondItemCustomFields)->toHaveCount(1);
});
