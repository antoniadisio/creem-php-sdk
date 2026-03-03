<?php

declare(strict_types=1);

namespace Creem\Dto\Subscription;

use function array_filter;

final class UpgradeSubscriptionRequest
{
    public function __construct(
        public readonly string $productId,
        public readonly ?string $updateBehavior = null,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        /** @var array<string, string> */
        return array_filter([
            'product_id' => $this->productId,
            'update_behavior' => $this->updateBehavior,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
