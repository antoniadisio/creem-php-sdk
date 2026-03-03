<?php

declare(strict_types=1);

namespace Creem\Dto\Transaction;

use function array_filter;

final class SearchTransactionsRequest
{
    public function __construct(
        public readonly ?string $customerId = null,
        public readonly ?string $orderId = null,
        public readonly ?string $productId = null,
        public readonly int|float|null $pageNumber = null,
        public readonly int|float|null $pageSize = null,
    ) {}

    /**
     * @return array<string, string|int|float>
     */
    public function toQuery(): array
    {
        /** @var array<string, string|int|float> */
        return array_filter([
            'customer_id' => $this->customerId,
            'order_id' => $this->orderId,
            'product_id' => $this->productId,
            'page_number' => $this->pageNumber,
            'page_size' => $this->pageSize,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
