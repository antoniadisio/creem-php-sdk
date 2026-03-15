<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests\Subscriptions;

use Antoniadisio\Creem\Internal\Http\Requests\JsonRequest;
use Antoniadisio\Creem\Internal\Http\Requests\PathIdentifier;
use Saloon\Enums\Method;

use function sprintf;

final class ResumeSubscriptionRequest extends JsonRequest
{
    protected Method $method = Method::POST;

    private readonly string $subscriptionId;

    public function __construct(
        string $subscriptionId,
        ?string $idempotencyKey = null,
    ) {
        $this->subscriptionId = PathIdentifier::normalize($subscriptionId, 'subscription ID');

        parent::__construct(idempotencyKey: $idempotencyKey);
    }

    public function resolveEndpoint(): string
    {
        return sprintf('/v1/subscriptions/%s/resume', $this->subscriptionId);
    }
}
