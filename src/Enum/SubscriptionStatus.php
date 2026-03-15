<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Canceled = 'canceled';
    case Unpaid = 'unpaid';
    case Paused = 'paused';
    case Trialing = 'trialing';
    case ScheduledCancel = 'scheduled_cancel';
}
