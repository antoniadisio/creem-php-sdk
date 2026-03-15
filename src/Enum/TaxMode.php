<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum TaxMode: string
{
    case Inclusive = 'inclusive';
    case Exclusive = 'exclusive';
}
