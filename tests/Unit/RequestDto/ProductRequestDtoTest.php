<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Dto\Common\CheckboxFieldConfigInput;
use Antoniadisio\Creem\Dto\Common\CustomFieldInput;
use Antoniadisio\Creem\Dto\Common\TextFieldConfigInput;
use Antoniadisio\Creem\Dto\Product\CreateProductRequest;
use Antoniadisio\Creem\Dto\Product\SearchProductsRequest;
use Antoniadisio\Creem\Enum\BillingPeriod;
use Antoniadisio\Creem\Enum\BillingType;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\CustomFieldType;
use Antoniadisio\Creem\Enum\TaxCategory;
use Antoniadisio\Creem\Enum\TaxMode;
use InvalidArgumentException;

test('product request dtos serialize pagination and typed custom fields', function (): void {
    $request = new CreateProductRequest(
        'Enterprise',
        4900,
        CurrencyCode::USD,
        BillingType::Recurring,
        description: 'Scale plan',
        imageUrl: 'https://example.com/product.png',
        billingPeriod: BillingPeriod::EveryMonth,
        taxMode: TaxMode::Exclusive,
        taxCategory: TaxCategory::Saas,
        defaultSuccessUrl: 'https://example.com/success',
        customFields: [
            new CustomFieldInput(
                CustomFieldType::Text,
                'companyName',
                'Company Name',
                optional: true,
                text: new TextFieldConfigInput(maxLength: 200, minLength: 1),
            ),
            new CustomFieldInput(
                CustomFieldType::Checkbox,
                'termsAccepted',
                'Terms Accepted',
                checkbox: new CheckboxFieldConfigInput('I agree to the terms'),
            ),
        ],
        abandonedCartRecoveryEnabled: true,
    );

    expect(new SearchProductsRequest(2, 50)->toQuery())->toBe([
        'page_number' => 2,
        'page_size' => 50,
    ])
        ->and($request->toArray())->toBe([
            'name' => 'Enterprise',
            'description' => 'Scale plan',
            'image_url' => 'https://example.com/product.png',
            'price' => 4900,
            'currency' => 'USD',
            'billing_type' => 'recurring',
            'billing_period' => 'every-month',
            'tax_mode' => 'exclusive',
            'tax_category' => 'saas',
            'default_success_url' => 'https://example.com/success',
            'custom_fields' => [
                [
                    'type' => 'text',
                    'key' => 'companyName',
                    'label' => 'Company Name',
                    'optional' => true,
                    'text' => [
                        'max_length' => 200,
                        'min_length' => 1,
                    ],
                ],
                [
                    'type' => 'checkbox',
                    'key' => 'termsAccepted',
                    'label' => 'Terms Accepted',
                    'checkbox' => [
                        'label' => 'I agree to the terms',
                    ],
                ],
            ],
            'abandoned_cart_recovery_enabled' => true,
        ]);

    expect($request->toArray())->not->toHaveKey('custom_field');
});

foreach (invalidProductRequestInputs() as $dataset => [$factory, $message]) {
    test("product request dtos reject invalid input ({$dataset})", function () use ($factory, $message): void {
        expect($factory)->toThrow(InvalidArgumentException::class, $message);
    });
}

/**
 * @return array<string, array{0: callable(): CreateProductRequest, 1: string}>
 */
function invalidProductRequestInputs(): array
{
    return [
        'non-positive price' => [
            static fn (): CreateProductRequest => new CreateProductRequest(
                'Enterprise',
                0,
                CurrencyCode::USD,
                BillingType::Recurring,
            ),
            'The product price must be greater than zero.',
        ],
        'invalid custom field type' => [
            static fn (): CreateProductRequest => new CreateProductRequest(
                'Enterprise',
                4900,
                CurrencyCode::USD,
                BillingType::Recurring,
                customFields: ['bad-field'],
            ),
            'Product custom field at index 0 must be an instance of Antoniadisio\\Creem\\Dto\\Common\\CustomFieldInput, string given.',
        ],
    ];
}
