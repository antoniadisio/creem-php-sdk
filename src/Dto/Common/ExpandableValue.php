<?php

declare(strict_types=1);

namespace Creem\Dto\Common;

use function is_array;
use function is_string;

final class ExpandableValue
{
    private function __construct(
        private readonly ?string $id,
        private readonly ?StructuredObject $resource,
    ) {}

    public static function fromValue(mixed $value): ?self
    {
        if (is_string($value)) {
            return new self($value, null);
        }

        if (! is_array($value)) {
            return null;
        }

        $id = isset($value['id']) && is_string($value['id']) ? $value['id'] : null;

        return new self($id, StructuredObject::fromArray($value));
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function resource(): ?StructuredObject
    {
        return $this->resource;
    }

    public function isExpanded(): bool
    {
        return $this->resource !== null;
    }
}
