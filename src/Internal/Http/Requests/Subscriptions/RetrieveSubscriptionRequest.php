<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests\Subscriptions;

use Antoniadisio\Creem\Internal\Http\Requests\QueryRequest;
use Saloon\Enums\Method;

final class RetrieveSubscriptionRequest extends QueryRequest
{
    protected Method $method = Method::GET;

    public function __construct(string $subscriptionId)
    {
        parent::__construct(['subscription_id' => $subscriptionId]);
    }

    public function resolveEndpoint(): string
    {
        return '/v1/subscriptions';
    }
}
