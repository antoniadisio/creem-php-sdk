<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum ProductFeatureType: string
{
    case Custom = 'custom';
    case File = 'file';
    case LicenseKey = 'licenseKey';
}
