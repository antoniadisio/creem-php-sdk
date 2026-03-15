<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Dto\Customer\CreateCustomerBillingPortalLinkRequest;
use Antoniadisio\Creem\Dto\Customer\ListCustomersRequest;
use InvalidArgumentException;

test('customer request dtos serialize customer identifiers and pagination', function (): void {
    expect(new CreateCustomerBillingPortalLinkRequest('cus_123')->toArray())->toBe([
        'customer_id' => 'cus_123',
    ])
        ->and(new ListCustomersRequest(1, 20)->toQuery())->toBe([
            'page_number' => 1,
            'page_size' => 20,
        ]);
});

foreach (invalidCustomerRequestInputs() as $dataset => [$factory, $message]) {
    test("customer request dtos reject invalid input ({$dataset})", function () use ($factory, $message): void {
        expect($factory)->toThrow(InvalidArgumentException::class, $message);
    });
}

/**
 * @return array<string, array{0: callable(): CreateCustomerBillingPortalLinkRequest, 1: string}>
 */
function invalidCustomerRequestInputs(): array
{
    return [
        'blank customer id' => [
            static fn (): CreateCustomerBillingPortalLinkRequest => new CreateCustomerBillingPortalLinkRequest('   '),
            'The customer ID cannot be blank.',
        ],
    ];
}
