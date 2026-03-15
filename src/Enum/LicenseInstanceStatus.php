<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum LicenseInstanceStatus: string
{
    case Active = 'active';
    case Deactivated = 'deactivated';
}
