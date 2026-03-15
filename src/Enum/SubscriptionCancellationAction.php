<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum SubscriptionCancellationAction: string
{
    case Cancel = 'cancel';
    case Pause = 'pause';
}
