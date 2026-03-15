<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests\Subscriptions;

use Antoniadisio\Creem\Internal\Http\Requests\JsonRequest;
use Antoniadisio\Creem\Internal\Http\Requests\PathIdentifier;
use Saloon\Enums\Method;

use function sprintf;

final class UpgradeSubscriptionRequest extends JsonRequest
{
    protected Method $method = Method::POST;

    private readonly string $subscriptionId;

    public function __construct(
        string $subscriptionId,
        array $payload = [],
        ?string $idempotencyKey = null,
    ) {
        $this->subscriptionId = PathIdentifier::normalize($subscriptionId, 'subscription ID');

        parent::__construct($payload, $idempotencyKey);
    }

    public function resolveEndpoint(): string
    {
        return sprintf('/v1/subscriptions/%s/upgrade', $this->subscriptionId);
    }
}
