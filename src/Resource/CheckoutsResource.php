<?php

declare(strict_types=1);

namespace Creem\Resource;

use Creem\Dto\Checkout\Checkout;
use Creem\Dto\Checkout\CreateCheckoutRequest;
use Creem\Internal\Http\Requests\Checkouts\CreateCheckoutRequest as CreateCheckoutOperation;
use Creem\Internal\Http\Requests\Checkouts\RetrieveCheckoutRequest;

final class CheckoutsResource extends Resource
{
    public function get(string $id): Checkout
    {
        return Checkout::fromPayload($this->send(new RetrieveCheckoutRequest($id)));
    }

    public function create(CreateCheckoutRequest $request): Checkout
    {
        return Checkout::fromPayload($this->send(new CreateCheckoutOperation($request->toArray())));
    }
}
