<?php

declare(strict_types=1);

namespace Creem\Internal\Hydration;

use Creem\Dto\Common\StructuredList;
use Creem\Dto\Common\StructuredObject;

use function array_is_list;
use function is_array;

final class StructuredValueNormalizer
{
    public static function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return StructuredList::fromArray($value);
        }

        return StructuredObject::fromArray($value);
    }
}
