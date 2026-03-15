<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum TaxCategory: string
{
    case Saas = 'saas';
    case DigitalGoodsService = 'digital-goods-service';
    case Ebooks = 'ebooks';
}
