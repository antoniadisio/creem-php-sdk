<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Resource;

use Antoniadisio\Creem\Dto\Discount\CreateDiscountRequest;
use Antoniadisio\Creem\Dto\Discount\Discount;
use Antoniadisio\Creem\Internal\Http\Requests\Discounts\CreateDiscountRequest as CreateDiscountOperation;
use Antoniadisio\Creem\Internal\Http\Requests\Discounts\DeleteDiscountRequest;
use Antoniadisio\Creem\Internal\Http\Requests\Discounts\RetrieveDiscountRequest;

final class DiscountsResource extends Resource
{
    public function get(string $id): Discount
    {
        return Discount::fromPayload($this->send(new RetrieveDiscountRequest($id)));
    }

    public function getByCode(string $code): Discount
    {
        return Discount::fromPayload($this->send(new RetrieveDiscountRequest(null, $code)));
    }

    public function create(CreateDiscountRequest $request, ?string $idempotencyKey = null): Discount
    {
        return Discount::fromPayload($this->send(new CreateDiscountOperation($request->toArray(), $idempotencyKey)));
    }

    public function delete(string $id, ?string $idempotencyKey = null): Discount
    {
        return Discount::fromPayload($this->send(new DeleteDiscountRequest($id, $idempotencyKey)));
    }
}
