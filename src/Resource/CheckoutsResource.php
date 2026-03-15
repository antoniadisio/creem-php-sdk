<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Resource;

use Antoniadisio\Creem\Dto\Checkout\Checkout;
use Antoniadisio\Creem\Dto\Checkout\CreateCheckoutRequest;
use Antoniadisio\Creem\Internal\Http\Requests\Checkouts\CreateCheckoutRequest as CreateCheckoutOperation;
use Antoniadisio\Creem\Internal\Http\Requests\Checkouts\RetrieveCheckoutRequest;

final class CheckoutsResource extends Resource
{
    public function get(string $id): Checkout
    {
        return Checkout::fromPayload($this->send(new RetrieveCheckoutRequest($id)));
    }

    public function create(CreateCheckoutRequest $request, ?string $idempotencyKey = null): Checkout
    {
        return Checkout::fromPayload($this->send(new CreateCheckoutOperation($request->toArray(), $idempotencyKey)));
    }
}
