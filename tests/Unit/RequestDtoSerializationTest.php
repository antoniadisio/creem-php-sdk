<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Dto\Checkout\CheckoutCustomerInput;
use Creem\Dto\Checkout\CreateCheckoutRequest;
use Creem\Dto\Common\CheckboxFieldConfigInput;
use Creem\Dto\Common\CustomFieldInput;
use Creem\Dto\Common\TextFieldConfigInput;
use Creem\Dto\Customer\CreateCustomerBillingPortalLinkRequest;
use Creem\Dto\Customer\ListCustomersRequest;
use Creem\Dto\Discount\CreateDiscountRequest;
use Creem\Dto\License\ActivateLicenseRequest;
use Creem\Dto\License\DeactivateLicenseRequest;
use Creem\Dto\License\ValidateLicenseRequest;
use Creem\Dto\Product\CreateProductRequest;
use Creem\Dto\Product\SearchProductsRequest;
use Creem\Dto\Stats\GetStatsSummaryRequest;
use Creem\Dto\Subscription\CancelSubscriptionRequest;
use Creem\Dto\Subscription\UpdateSubscriptionRequest;
use Creem\Dto\Subscription\UpgradeSubscriptionRequest;
use Creem\Dto\Subscription\UpsertSubscriptionItem;
use Creem\Dto\Transaction\SearchTransactionsRequest;
use Creem\Enum\BillingPeriod;
use Creem\Enum\BillingType;
use Creem\Enum\CurrencyCode;
use Creem\Enum\CustomFieldType;
use Creem\Enum\DiscountDuration;
use Creem\Enum\DiscountType;
use Creem\Enum\StatsInterval;
use Creem\Enum\SubscriptionCancellationAction;
use Creem\Enum\SubscriptionCancellationMode;
use Creem\Enum\SubscriptionUpdateBehavior;
use Creem\Enum\TaxCategory;
use Creem\Enum\TaxMode;
use DateTimeImmutable;

test('basic request dtos serialize through the shared normalizer', function (): void {
    $this->assertSame([
        'customer_id' => 'cus_123',
    ], new CreateCustomerBillingPortalLinkRequest('cus_123')->toArray());

    $this->assertSame([
        'key' => 'lic_key',
        'instance_name' => 'macbook',
    ], new ActivateLicenseRequest('lic_key', 'macbook')->toArray());

    $this->assertSame([
        'key' => 'lic_key',
        'instance_id' => 'ins_123',
    ], new DeactivateLicenseRequest('lic_key', 'ins_123')->toArray());

    $this->assertSame([
        'key' => 'lic_key',
        'instance_id' => 'ins_456',
    ], new ValidateLicenseRequest('lic_key', 'ins_456')->toArray());
});

test('query request dtos serialize integer pagination', function (): void {
    $this->assertSame([
        'page_number' => 2,
        'page_size' => 50,
    ], new SearchProductsRequest(2, 50)->toQuery());

    $this->assertSame([
        'page_number' => 1,
        'page_size' => 20,
    ], new ListCustomersRequest(1, 20)->toQuery());

    $this->assertSame([
        'customer_id' => 'cus_123',
        'order_id' => 'ord_123',
        'product_id' => 'prod_123',
        'page_number' => 3,
        'page_size' => 25,
    ], new SearchTransactionsRequest('cus_123', 'ord_123', 'prod_123', 3, 25)->toQuery());
});

test('create product request serializes typed custom fields', function (): void {
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

    $this->assertSame([
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
    ], $request->toArray());

    $this->assertArrayNotHasKey('custom_field', $request->toArray());
});

test('create discount request serializes enums and rfc3339 dates', function (): void {
    $request = new CreateDiscountRequest(
        'Launch',
        DiscountType::Fixed,
        DiscountDuration::Repeating,
        ['prod_123', 'prod_456'],
        code: 'LAUNCH20',
        amount: 2000,
        currency: CurrencyCode::EUR,
        expiryDate: new DateTimeImmutable('2024-12-31T23:59:59Z'),
        maxRedemptions: 100,
        durationInMonths: 6,
    );

    $this->assertSame([
        'name' => 'Launch',
        'code' => 'LAUNCH20',
        'type' => 'fixed',
        'amount' => 2000,
        'currency' => 'EUR',
        'expiry_date' => '2024-12-31T23:59:59+00:00',
        'max_redemptions' => 100,
        'duration' => 'repeating',
        'duration_in_months' => 6,
        'applies_to_products' => ['prod_123', 'prod_456'],
    ], $request->toArray());
});

test('checkout and subscription requests serialize nested inputs and enums', function (): void {
    $checkoutRequest = new CreateCheckoutRequest(
        'prod_123',
        requestId: 'req_123',
        units: 2,
        discountCode: 'SUMMER2024',
        customer: new CheckoutCustomerInput(email: 'user@example.com'),
        customFields: [
            new CustomFieldInput(
                CustomFieldType::Text,
                'companyName',
                'Company Name',
                text: new TextFieldConfigInput(maxLength: 100),
            ),
        ],
        successUrl: 'https://example.com/success',
        metadata: ['source' => 'email'],
    );

    $this->assertSame([
        'request_id' => 'req_123',
        'product_id' => 'prod_123',
        'units' => 2,
        'discount_code' => 'SUMMER2024',
        'customer' => [
            'email' => 'user@example.com',
        ],
        'custom_fields' => [
            [
                'type' => 'text',
                'key' => 'companyName',
                'label' => 'Company Name',
                'text' => [
                    'max_length' => 100,
                ],
            ],
        ],
        'success_url' => 'https://example.com/success',
        'metadata' => ['source' => 'email'],
    ], $checkoutRequest->toArray());
    $this->assertArrayNotHasKey('custom_field', $checkoutRequest->toArray());

    $cancelRequest = new CancelSubscriptionRequest(
        SubscriptionCancellationMode::Scheduled,
        SubscriptionCancellationAction::Pause,
    );
    $this->assertSame([
        'mode' => 'scheduled',
        'onExecute' => 'pause',
    ], $cancelRequest->toArray());

    $updateRequest = new UpdateSubscriptionRequest(
        [
            new UpsertSubscriptionItem(id: 'item_123', productId: 'prod_123', priceId: 'price_123', units: 4),
        ],
        SubscriptionUpdateBehavior::ProrationNone,
    );
    $this->assertSame([
        'items' => [
            [
                'id' => 'item_123',
                'product_id' => 'prod_123',
                'price_id' => 'price_123',
                'units' => 4,
            ],
        ],
        'update_behavior' => 'proration-none',
    ], $updateRequest->toArray());

    $upgradeRequest = new UpgradeSubscriptionRequest(
        'prod_999',
        SubscriptionUpdateBehavior::ProrationChargeImmediately,
    );
    $this->assertSame([
        'product_id' => 'prod_999',
        'update_behavior' => 'proration-charge-immediately',
    ], $upgradeRequest->toArray());
});

test('stats request serializes millisecond timestamps', function (): void {
    $request = new GetStatsSummaryRequest(
        CurrencyCode::USD,
        new DateTimeImmutable('2023-11-14T22:13:20.123+00:00'),
        new DateTimeImmutable('2023-11-15T22:13:20.456+00:00'),
        StatsInterval::Week,
    );

    $this->assertSame([
        'startDate' => 1700000000123,
        'endDate' => 1700086400456,
        'interval' => 'week',
        'currency' => 'USD',
    ], $request->toQuery());
});
