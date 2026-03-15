<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Dto\Checkout\CheckoutCustomerInput;
use Antoniadisio\Creem\Dto\Checkout\CreateCheckoutRequest;
use Antoniadisio\Creem\Dto\Common\CustomFieldInput;
use Antoniadisio\Creem\Dto\Common\TextFieldConfigInput;
use Antoniadisio\Creem\Enum\CustomFieldType;
use InvalidArgumentException;

test('checkout request dtos serialize nested inputs and metadata', function (): void {
    $request = new CreateCheckoutRequest(
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

    expect($request->toArray())->toBe([
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
    ]);

    expect($request->toArray())->not->toHaveKey('custom_field');
});

foreach (invalidCheckoutRequestInputs() as $dataset => [$factory, $message]) {
    test("checkout request dtos reject invalid input ({$dataset})", function () use ($factory, $message): void {
        expect($factory)->toThrow(InvalidArgumentException::class, $message);
    });
}

/**
 * @return array<string, array{0: callable(): CreateCheckoutRequest, 1: string}>
 */
function invalidCheckoutRequestInputs(): array
{
    return [
        'non-positive units' => [
            static fn (): CreateCheckoutRequest => new CreateCheckoutRequest('prod_123', units: 0),
            'The checkout units must be greater than zero.',
        ],
        'invalid custom field type' => [
            static fn (): CreateCheckoutRequest => new CreateCheckoutRequest(
                'prod_123',
                customFields: ['bad-field'],
            ),
            'Checkout custom field at index 0 must be an instance of Antoniadisio\\Creem\\Dto\\Common\\CustomFieldInput, string given.',
        ],
    ];
}
