<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum LicenseStatus: string
{
    case Inactive = 'inactive';
    case Active = 'active';
    case Expired = 'expired';
    case Disabled = 'disabled';
}
