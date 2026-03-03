<?php

declare(strict_types=1);

namespace Creem\Dto\Subscription;

use Creem\Enum\SubscriptionUpdateBehavior;
use Creem\Internal\Serialization\RequestValueNormalizer;

final readonly class UpdateSubscriptionRequest
{
    /**
     * @param  list<UpsertSubscriptionItem>  $items
     */
    public function __construct(
        public array $items = [],
        public ?SubscriptionUpdateBehavior $updateBehavior = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return RequestValueNormalizer::payload([
            'items' => $this->items,
            'update_behavior' => $this->updateBehavior,
        ]);
    }
}
