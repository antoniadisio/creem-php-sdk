<?php

declare(strict_types=1);

namespace Creem\Tests\Smoke;

use Creem\Dto\Discount\Discount;
use Creem\Tests\SmokeTestCase;

test('smoke discount retrieval returns a typed discount when a discount id is configured', function (): void {
    /** @var SmokeTestCase $this */
    $discountId = $this->requireOptionalSmokeValue('CREEM_TEST_DISCOUNT_ID', 'discounts()->get()');
    $discount = $this->smokeClient()->discounts()->get($discountId);

    expect($discount)->toBeInstanceOf(Discount::class)
        ->and($discount->id)->toBe($discountId);
});

test('smoke discount code lookup returns a typed discount when a code is configured', function (): void {
    /** @var SmokeTestCase $this */
    $code = $this->requireOptionalSmokeValue('CREEM_TEST_DISCOUNT_CODE', 'discounts()->getByCode()');
    $discount = $this->smokeClient()->discounts()->getByCode($code);

    expect($discount)->toBeInstanceOf(Discount::class)
        ->and($discount->code)->toBe($code);
});
