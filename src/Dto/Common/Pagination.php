<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Common;

final readonly class Pagination
{
    public function __construct(
        public ?int $totalRecords,
        public ?int $totalPages,
        public ?int $currentPage,
        public ?int $nextPage,
        public ?int $prevPage,
    ) {}
}
