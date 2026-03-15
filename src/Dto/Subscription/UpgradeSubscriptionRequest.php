<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Subscription;

use Antoniadisio\Creem\Enum\SubscriptionUpdateBehavior;
use Antoniadisio\Creem\Internal\Serialization\RequestValueNormalizer;
use InvalidArgumentException;

use function trim;

final readonly class UpgradeSubscriptionRequest
{
    public string $productId;

    public function __construct(
        string $productId,
        public ?SubscriptionUpdateBehavior $updateBehavior = null,
    ) {
        $productId = trim($productId);

        if ($productId === '') {
            throw new InvalidArgumentException('The subscription upgrade product ID cannot be blank.');
        }

        $this->productId = $productId;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        /** @var array<string, string> */
        return RequestValueNormalizer::payload([
            'product_id' => $this->productId,
            'update_behavior' => $this->updateBehavior,
        ]);
    }
}
