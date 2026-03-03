<?php

declare(strict_types=1);

namespace Creem\Dto\Subscription;

use function array_filter;

final class UpdateSubscriptionRequest
{
    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function __construct(
        public readonly array $items = [],
        public readonly ?string $updateBehavior = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'items' => $this->items,
            'update_behavior' => $this->updateBehavior,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
