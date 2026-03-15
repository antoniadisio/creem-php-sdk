<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum OrderType: string
{
    case Recurring = 'recurring';
    case OneTime = 'onetime';
}
