<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Common;

use Antoniadisio\Creem\Internal\Hydration\StructuredValueNormalizer;
use IteratorAggregate;
use Traversable;

use function array_map;
use function count;

/**
 * @implements IteratorAggregate<int, mixed>
 */
final readonly class StructuredList implements IteratorAggregate
{
    /**
     * @param  list<mixed>  $items
     */
    private function __construct(
        private array $items,
    ) {}

    /**
     * @param  list<mixed>  $items
     */
    public static function fromArray(array $items): self
    {
        /** @var list<mixed> $normalized */
        $normalized = array_map(
            StructuredValueNormalizer::normalize(...),
            $items,
        );

        return new self($normalized);
    }

    /**
     * @return list<mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function get(int $index): mixed
    {
        return $this->items[$index] ?? null;
    }

    /**
     * @return Traversable<int, mixed>
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
