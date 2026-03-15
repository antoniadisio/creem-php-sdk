<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum TransactionType: string
{
    case Payment = 'payment';
    case Invoice = 'invoice';
}
