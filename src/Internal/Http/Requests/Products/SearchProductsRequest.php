<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests\Products;

use Antoniadisio\Creem\Internal\Http\Requests\QueryRequest;
use Saloon\Enums\Method;

final class SearchProductsRequest extends QueryRequest
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/v1/products/search';
    }
}
