<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum SubscriptionUpdateBehavior: string
{
    case ProrationChargeImmediately = 'proration-charge-immediately';
    case ProrationCharge = 'proration-charge';
    case ProrationNone = 'proration-none';
}
