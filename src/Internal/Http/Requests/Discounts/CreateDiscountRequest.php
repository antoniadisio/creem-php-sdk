<?php

declare(strict_types=1);

namespace Creem\Internal\Http\Requests\Discounts;

use Creem\Internal\Http\Requests\JsonRequest;
use Saloon\Enums\Method;

final class CreateDiscountRequest extends JsonRequest
{
    protected Method $method = Method::POST;

    public function resolveEndpoint(): string
    {
        return '/v1/discounts';
    }
}
