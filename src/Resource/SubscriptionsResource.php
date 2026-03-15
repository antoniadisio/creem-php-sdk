<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Resource;

use Antoniadisio\Creem\Dto\Subscription\CancelSubscriptionRequest;
use Antoniadisio\Creem\Dto\Subscription\Subscription;
use Antoniadisio\Creem\Dto\Subscription\UpdateSubscriptionRequest;
use Antoniadisio\Creem\Dto\Subscription\UpgradeSubscriptionRequest;
use Antoniadisio\Creem\Internal\Http\Requests\Subscriptions\CancelSubscriptionRequest as CancelSubscriptionOperation;
use Antoniadisio\Creem\Internal\Http\Requests\Subscriptions\PauseSubscriptionRequest;
use Antoniadisio\Creem\Internal\Http\Requests\Subscriptions\ResumeSubscriptionRequest;
use Antoniadisio\Creem\Internal\Http\Requests\Subscriptions\RetrieveSubscriptionRequest;
use Antoniadisio\Creem\Internal\Http\Requests\Subscriptions\UpdateSubscriptionRequest as UpdateSubscriptionOperation;
use Antoniadisio\Creem\Internal\Http\Requests\Subscriptions\UpgradeSubscriptionRequest as UpgradeSubscriptionOperation;

final class SubscriptionsResource extends Resource
{
    public function get(string $id): Subscription
    {
        return Subscription::fromPayload($this->send(new RetrieveSubscriptionRequest($id)));
    }

    public function cancel(string $id, ?CancelSubscriptionRequest $request = null, ?string $idempotencyKey = null): Subscription
    {
        $request ??= new CancelSubscriptionRequest;

        return Subscription::fromPayload($this->send(new CancelSubscriptionOperation($id, $request->toArray(), $idempotencyKey)));
    }

    public function update(string $id, UpdateSubscriptionRequest $request, ?string $idempotencyKey = null): Subscription
    {
        return Subscription::fromPayload($this->send(new UpdateSubscriptionOperation($id, $request->toArray(), $idempotencyKey)));
    }

    public function upgrade(string $id, UpgradeSubscriptionRequest $request, ?string $idempotencyKey = null): Subscription
    {
        return Subscription::fromPayload($this->send(new UpgradeSubscriptionOperation($id, $request->toArray(), $idempotencyKey)));
    }

    public function pause(string $id, ?string $idempotencyKey = null): Subscription
    {
        return Subscription::fromPayload($this->send(new PauseSubscriptionRequest($id, $idempotencyKey)));
    }

    public function resume(string $id, ?string $idempotencyKey = null): Subscription
    {
        return Subscription::fromPayload($this->send(new ResumeSubscriptionRequest($id, $idempotencyKey)));
    }
}
