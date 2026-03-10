<?php

declare(strict_types=1);

namespace Creem\Tests\Smoke;

use Creem\Dto\Checkout\Checkout;
use Creem\Tests\SmokeTestCase;

test('smoke checkout retrieval returns a typed checkout when a checkout id is configured', function (): void {
    /** @var SmokeTestCase $this */
    $checkoutId = $this->requireOptionalSmokeValue('CREEM_TEST_CHECKOUT_ID', 'checkouts()->get()');
    $checkout = $this->smokeClient()->checkouts()->get($checkoutId);

    expect($checkout)->toBeInstanceOf(Checkout::class)
        ->and($checkout->id)->toBe($checkoutId);
});
