<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Hydration;

use Antoniadisio\Creem\Dto\Common\StructuredList;
use Antoniadisio\Creem\Dto\Common\StructuredObject;

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

        /** @var array<string, mixed> $value */
        return StructuredObject::fromArray($value);
    }
}
