<?php

declare(strict_types=1);

namespace Creem\Internal\Http\Requests\Subscriptions;

use Creem\Internal\Http\Requests\JsonRequest;
use Saloon\Enums\Method;

use function sprintf;

final class CancelSubscriptionRequest extends JsonRequest
{
    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $subscriptionId,
        array $payload = [],
    ) {
        parent::__construct($payload);
    }

    public function resolveEndpoint(): string
    {
        return sprintf('/v1/subscriptions/%s/cancel', $this->subscriptionId);
    }
}
