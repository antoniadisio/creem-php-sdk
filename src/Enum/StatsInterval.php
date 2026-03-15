<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum StatsInterval: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
}
