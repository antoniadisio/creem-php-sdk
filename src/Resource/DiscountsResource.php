<?php

declare(strict_types=1);

namespace Creem\Resource;

use Creem\Dto\Discount\CreateDiscountRequest;
use Creem\Dto\Discount\Discount;
use Creem\Internal\Http\Requests\Discounts\CreateDiscountRequest as CreateDiscountOperation;
use Creem\Internal\Http\Requests\Discounts\DeleteDiscountRequest;
use Creem\Internal\Http\Requests\Discounts\RetrieveDiscountRequest;

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

    public function create(CreateDiscountRequest $request): Discount
    {
        return Discount::fromPayload($this->send(new CreateDiscountOperation($request->toArray())));
    }

    public function delete(string $id): Discount
    {
        return Discount::fromPayload($this->send(new DeleteDiscountRequest($id)));
    }
}
