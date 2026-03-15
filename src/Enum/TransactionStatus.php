<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Refunded = 'refunded';
    case PartialRefund = 'partialRefund';
    case ChargedBack = 'chargedBack';
    case Uncollectible = 'uncollectible';
    case Declined = 'declined';
    case Void = 'void';
}
