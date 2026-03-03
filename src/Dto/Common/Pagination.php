<?php

declare(strict_types=1);

namespace Creem\Dto\Common;

final class Pagination
{
    public function __construct(
        public readonly ?int $totalRecords,
        public readonly ?int $totalPages,
        public readonly ?int $currentPage,
        public readonly ?int $nextPage,
        public readonly ?int $prevPage,
    ) {}
}
