<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
}
