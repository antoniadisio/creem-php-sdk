<?php

declare(strict_types=1);

namespace Creem\Dto\Customer;

use function array_filter;

final class ListCustomersRequest
{
    public function __construct(
        public readonly int|float|null $pageNumber = null,
        public readonly int|float|null $pageSize = null,
    ) {}

    /**
     * @return array<string, int|float>
     */
    public function toQuery(): array
    {
        /** @var array<string, int|float> */
        return array_filter([
            'page_number' => $this->pageNumber,
            'page_size' => $this->pageSize,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
