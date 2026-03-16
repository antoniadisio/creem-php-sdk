<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Enum\WebhookEventType;

test('webhook event enum matches documented event values', function (): void {
    expect(array_map(
        static fn (WebhookEventType $case): string => $case->value,
        WebhookEventType::cases(),
    ))->toBe([
        'checkout.completed',
        'subscription.active',
        'subscription.paid',
        'subscription.canceled',
        'subscription.scheduled_cancel',
        'subscription.past_due',
        'subscription.expired',
        'subscription.trialing',
        'subscription.paused',
        'subscription.update',
        'refund.created',
        'dispute.created',
    ]);
});
