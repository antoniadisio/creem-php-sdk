<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum CheckoutStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Expired = 'expired';
}
