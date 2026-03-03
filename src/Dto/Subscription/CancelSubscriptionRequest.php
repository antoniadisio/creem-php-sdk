<?php

declare(strict_types=1);

namespace Creem\Dto\Subscription;

use function array_filter;

final class CancelSubscriptionRequest
{
    public function __construct(
        public readonly ?string $mode = null,
        public readonly ?string $onExecute = null,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        /** @var array<string, string> */
        return array_filter([
            'mode' => $this->mode,
            'onExecute' => $this->onExecute,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
