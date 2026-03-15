<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests\Customers;

use Antoniadisio\Creem\Internal\Http\Requests\QueryRequest;
use Saloon\Enums\Method;

final class ListCustomersRequest extends QueryRequest
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/v1/customers/list';
    }
}
