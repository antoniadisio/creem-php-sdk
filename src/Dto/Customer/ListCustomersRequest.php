<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Customer;

use Antoniadisio\Creem\Internal\Serialization\RequestValueNormalizer;

final readonly class ListCustomersRequest
{
    public function __construct(
        public ?int $pageNumber = null,
        public ?int $pageSize = null,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toQuery(): array
    {
        /** @var array<string, int> */
        return RequestValueNormalizer::query([
            'page_number' => $this->pageNumber,
            'page_size' => $this->pageSize,
        ]);
    }
}
