<?php

declare(strict_types=1);

namespace Creem\Dto\Subscription;

use Creem\Internal\Serialization\RequestValueNormalizer;

final readonly class UpsertSubscriptionItem
{
    public function __construct(
        public ?string $id = null,
        public ?string $productId = null,
        public ?string $priceId = null,
        public ?int $units = null,
    ) {}

    /**
     * @return array<string, string|int>
     */
    public function toArray(): array
    {
        /** @var array<string, string|int> */
        return RequestValueNormalizer::payload([
            'id' => $this->id,
            'product_id' => $this->productId,
            'price_id' => $this->priceId,
            'units' => $this->units,
        ]);
    }
}
