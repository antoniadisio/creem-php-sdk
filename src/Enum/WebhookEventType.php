<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum WebhookEventType: string
{
    case CheckoutCompleted = 'checkout.completed';
    case SubscriptionActive = 'subscription.active';
    case SubscriptionPaid = 'subscription.paid';
    case SubscriptionCanceled = 'subscription.canceled';
    case SubscriptionScheduledCancel = 'subscription.scheduled_cancel';
    case SubscriptionPastDue = 'subscription.past_due';
    case SubscriptionExpired = 'subscription.expired';
    case SubscriptionTrialing = 'subscription.trialing';
    case SubscriptionPaused = 'subscription.paused';
    case SubscriptionUpdate = 'subscription.update';
    case RefundCreated = 'refund.created';
    case DisputeCreated = 'dispute.created';
}
