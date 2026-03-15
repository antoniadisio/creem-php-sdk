<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum BillingPeriod: string
{
    case EveryMonth = 'every-month';
    case EveryThreeMonths = 'every-three-months';
    case EverySixMonths = 'every-six-months';
    case EveryYear = 'every-year';
    case Once = 'once';
}
