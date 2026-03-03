<?php

declare(strict_types=1);

namespace Creem\Dto\Common;

final class Pagination
{
    public function __construct(
        public readonly int|float|null $totalRecords,
        public readonly int|float|null $totalPages,
        public readonly int|float|null $currentPage,
        public readonly int|float|null $nextPage,
        public readonly int|float|null $prevPage,
    ) {}
}
