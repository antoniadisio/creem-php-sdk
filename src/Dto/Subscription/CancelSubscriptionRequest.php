<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Subscription;

use Antoniadisio\Creem\Enum\SubscriptionCancellationAction;
use Antoniadisio\Creem\Enum\SubscriptionCancellationMode;
use Antoniadisio\Creem\Internal\Serialization\RequestValueNormalizer;

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
