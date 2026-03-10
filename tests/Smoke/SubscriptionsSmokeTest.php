<?php

declare(strict_types=1);

namespace Creem\Tests\Smoke;

use Creem\Dto\Subscription\Subscription;
use Creem\Tests\SmokeTestCase;

test('smoke subscription retrieval returns a typed subscription when a subscription id is configured', function (): void {
    /** @var SmokeTestCase $this */
    $subscriptionId = $this->requireOptionalSmokeValue('CREEM_TEST_SUBSCRIPTION_ID', 'subscriptions()->get()');
    $subscription = $this->smokeClient()->subscriptions()->get($subscriptionId);

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->id)->toBe($subscriptionId);
});
