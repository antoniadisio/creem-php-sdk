<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum DiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
}
