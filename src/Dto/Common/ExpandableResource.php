<?php

declare(strict_types=1);

namespace Creem\Dto\Common;

/**
 * @template TResource of object
 */
final class ExpandableResource
{
    /**
     * @param  TResource|null  $resource
     */
    private function __construct(
        private readonly string $id,
        private readonly ?object $resource,
    ) {}

    /**
     * @return self<TResource>
     */
    public static function fromId(string $id): self
    {
        /** @var self<TResource> $resource */
        $resource = new self($id, null);

        return $resource;
    }

    /**
     * @template TExpanded of object
     *
     * @param  TExpanded  $resource
     * @return self<TExpanded>
     */
    public static function expanded(string $id, object $resource): self
    {
        /** @var self<TExpanded> $expandedResource */
        $expandedResource = new self($id, $resource);

        return $expandedResource;
    }

    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return TResource|null
     */
    public function resource(): ?object
    {
        return $this->resource;
    }

    public function isExpanded(): bool
    {
        return $this->resource !== null;
    }
}
