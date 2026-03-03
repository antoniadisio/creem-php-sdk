<?php

declare(strict_types=1);

namespace Creem\Dto\Common;

use function array_is_list;
use function is_array;
use function is_string;

/**
 * @deprecated Use ExpandableResource<TResource> for new typed payload mappings.
 */
final readonly class ExpandableValue
{
    private function __construct(
        private ?string $id,
        private ?StructuredObject $resource,
    ) {}

    public static function fromValue(mixed $value): ?self
    {
        if (is_string($value)) {
            return new self($value, null);
        }

        if (! is_array($value) || array_is_list($value)) {
            return null;
        }

        /** @var array<string, mixed> $value */
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
        return $this->resource instanceof \Creem\Dto\Common\StructuredObject;
    }
}
