<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Common;

use IteratorAggregate;
use Traversable;

use function count;

/**
 * @template TItem
 *
 * @implements IteratorAggregate<int, TItem>
 */
final readonly class Page implements IteratorAggregate
{
    /**
     * @param  list<TItem>  $items
     */
    public function __construct(
        private array $items,
        public ?Pagination $pagination,
    ) {}

    /**
     * @return list<TItem>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return TItem|null
     */
    public function get(int $index): mixed
    {
        return $this->items[$index] ?? null;
    }

    public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
