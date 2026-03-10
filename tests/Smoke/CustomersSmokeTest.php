<?php

declare(strict_types=1);

namespace Creem\Tests\Smoke;

use Creem\Dto\Customer\Customer;
use Creem\Dto\Customer\ListCustomersRequest;
use Creem\Tests\SmokeTestCase;

test('smoke customers list returns a typed page', function (): void {
    /** @var SmokeTestCase $this */
    $page = $this->smokeClient()->customers()->list(new ListCustomersRequest(pageSize: 1));

    $this->assertTypedSmokePage($page, Customer::class);
});

test('smoke customer retrieval returns a typed customer when a customer id is configured', function (): void {
    /** @var SmokeTestCase $this */
    $customerId = $this->requireOptionalSmokeValue('CREEM_TEST_CUSTOMER_ID', 'customers()->get()');
    $customer = $this->smokeClient()->customers()->get($customerId);

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->id)->toBe($customerId);
});

test('smoke customer email lookup returns a typed customer when an email is configured', function (): void {
    /** @var SmokeTestCase $this */
    $email = $this->requireOptionalSmokeValue('CREEM_TEST_CUSTOMER_EMAIL', 'customers()->findByEmail()');
    $customer = $this->smokeClient()->customers()->findByEmail($email);

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->email)->toBe($email);
});
