<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Dto\Checkout\Checkout;
use Antoniadisio\Creem\Dto\Common\CustomField;
use Antoniadisio\Creem\Dto\Common\ProductFeature;
use Antoniadisio\Creem\Dto\Discount\Discount;
use Antoniadisio\Creem\Dto\Product\Product;
use Antoniadisio\Creem\Dto\Subscription\Subscription;
use Antoniadisio\Creem\Enum\CustomFieldType;
use Antoniadisio\Creem\Enum\ProductFeatureType;
use Antoniadisio\Creem\Exception\HydrationException;
use Antoniadisio\Creem\Tests\TestCase;

test('custom field hydration supports checkbox variants', function (): void {
    $field = CustomField::fromPayload([
        'type' => 'checkbox',
        'key' => 'termsAccepted',
        'label' => 'Terms Accepted',
        'optional' => true,
        'checkbox' => [
            'label' => 'I agree to the terms',
            'value' => true,
        ],
    ]);

    expect($field->type)->toBe(CustomFieldType::Checkbox)
        ->and($field->key)->toBe('termsAccepted')
        ->and($field->label)->toBe('Terms Accepted')
        ->and($field->optional)->toBeTrue()
        ->and($field->text)->toBeNull()
        ->and($field->checkbox?->label)->toBe('I agree to the terms')
        ->and($field->checkbox?->value)->toBeTrue();
});

test('product feature hydration supports license key variants', function (): void {
    /** @var TestCase $this */
    $feature = ProductFeature::fromPayload([
        'id' => 'feat_1',
        'description' => 'License access',
        'type' => 'licenseKey',
        'license_key' => $this->fixture('license.json'),
    ]);

    expect($feature->type)->toBe(ProductFeatureType::LicenseKey)
        ->and($feature->licenseKey?->id)->toBe('lk_fixture_primary')
        ->and($feature->license)->toBeNull();
});

test('product feature hydration supports license variants', function (): void {
    /** @var TestCase $this */
    $feature = ProductFeature::fromPayload([
        'id' => 'feat_2',
        'description' => 'License object',
        'license' => $this->fixture('license.json'),
    ]);

    expect($feature->type)->toBeNull()
        ->and($feature->license?->id)->toBe('lk_fixture_primary')
        ->and($feature->licenseKey)->toBeNull();
});

test('checkout hydration supports a single feature object', function (): void {
    /** @var TestCase $this */
    $checkout = Checkout::fromPayload([
        ...$this->fixture('checkout.json'),
        'feature' => [
            'id' => 'feat_checkout_primary',
            'description' => 'Issued license key',
            'type' => 'licenseKey',
            'license_key' => $this->fixture('license.json'),
        ],
    ]);

    expect($checkout->feature)->toHaveCount(1)
        ->and($checkout->feature[0])->toBeInstanceOf(ProductFeature::class)
        ->and($checkout->feature[0]->id)->toBe('feat_checkout_primary')
        ->and($checkout->feature[0]->type)->toBe(ProductFeatureType::LicenseKey)
        ->and($checkout->feature[0]->licenseKey?->id)->toBe('lk_fixture_primary');
});

test('product hydration supports custom field variants', function (): void {
    /** @var TestCase $this */
    $product = Product::fromPayload($this->fixture('product.json'));

    expect($product->customFields)->toHaveCount(2)
        ->and($product->customFields[0]->type)->toBe(CustomFieldType::Text)
        ->and($product->customFields[0]->key)->toBe('company_name')
        ->and($product->customFields[0]->text?->value)->toBe('Example Company')
        ->and($product->customFields[1]->type)->toBe(CustomFieldType::Checkbox)
        ->and($product->customFields[1]->checkbox?->value)->toBeTrue();
});

foreach (resourceDtoHydrationFailures() as $dataset => [$factory, $message]) {
    test("resource dto hydration rejects malformed nested payloads ({$dataset})", function () use ($factory, $message): void {
        /** @var TestCase $this */
        expect(fn () => $factory($this))
            ->toThrow(HydrationException::class, $message);
    });
}

/**
 * @return array<string, array{0: callable(TestCase): mixed, 1: string}>
 */
function resourceDtoHydrationFailures(): array
{
    return [
        'checkout custom fields' => [
            static function (TestCase $testCase): Checkout {
                $payload = $testCase->fixture('checkout.json');
                $payload['custom_fields'] = ['invalid'];

                return Checkout::fromPayload($payload);
            },
            'Hydration failed for Checkout.custom_fields: expected object, got string.',
        ],
        'checkout features' => [
            static function (TestCase $testCase): Checkout {
                $payload = $testCase->fixture('checkout.json');
                $payload['feature'] = ['invalid'];

                return Checkout::fromPayload($payload);
            },
            'Hydration failed for Checkout.feature: expected object, got string.',
        ],
        'product custom fields' => [
            static function (TestCase $testCase): Product {
                $payload = $testCase->fixture('product.json');
                $payload['custom_fields'] = ['invalid'];

                return Product::fromPayload($payload);
            },
            'Hydration failed for Product.custom_fields: expected object, got string.',
        ],
        'subscription items' => [
            static function (TestCase $testCase): Subscription {
                $payload = $testCase->fixture('subscription.json');
                $payload['items'] = ['invalid'];

                return Subscription::fromPayload($payload);
            },
            'Hydration failed for Subscription.items: expected object, got string.',
        ],
        'discount applies to products' => [
            static function (TestCase $testCase): Discount {
                $payload = $testCase->fixture('discount.json');
                $payload['applies_to_products'] = [123];

                return Discount::fromPayload($payload);
            },
            'Hydration failed for Discount.applies_to_products: expected string, got int.',
        ],
    ];
}
