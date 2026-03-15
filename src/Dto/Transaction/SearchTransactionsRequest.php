<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Transaction;

use Antoniadisio\Creem\Internal\Serialization\RequestValueNormalizer;

final readonly class SearchTransactionsRequest
{
    public function __construct(
        public ?string $customerId = null,
        public ?string $orderId = null,
        public ?string $productId = null,
        public ?int $pageNumber = null,
        public ?int $pageSize = null,
    ) {}

    /**
     * @return array<string, string|int>
     */
    public function toQuery(): array
    {
        /** @var array<string, string|int> */
        return RequestValueNormalizer::query([
            'customer_id' => $this->customerId,
            'order_id' => $this->orderId,
            'product_id' => $this->productId,
            'page_number' => $this->pageNumber,
            'page_size' => $this->pageSize,
        ]);
    }
}
