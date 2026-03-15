<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum SubscriptionCancellationMode: string
{
    case Immediate = 'immediate';
    case Scheduled = 'scheduled';
}
