<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests\Customers;

use Antoniadisio\Creem\Internal\Http\Requests\JsonRequest;
use Saloon\Enums\Method;

final class GenerateCustomerLinksRequest extends JsonRequest
{
    protected Method $method = Method::POST;

    public function resolveEndpoint(): string
    {
        return '/v1/customers/billing';
    }
}
