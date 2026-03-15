<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum ProductStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
}
