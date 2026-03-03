<?php

declare(strict_types=1);

namespace Creem\Dto\Subscription;

use Creem\Enum\SubscriptionCancellationAction;
use Creem\Enum\SubscriptionCancellationMode;
use Creem\Internal\Serialization\RequestValueNormalizer;

final readonly class CancelSubscriptionRequest
{
    public function __construct(
        public ?SubscriptionCancellationMode $mode = null,
        public ?SubscriptionCancellationAction $onExecute = null,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        /** @var array<string, string> */
        return RequestValueNormalizer::payload([
            'mode' => $this->mode,
            'onExecute' => $this->onExecute,
        ]);
    }
}
