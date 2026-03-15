<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests\Products;

use Antoniadisio\Creem\Internal\Http\Requests\QueryRequest;
use Saloon\Enums\Method;

final class RetrieveProductRequest extends QueryRequest
{
    protected Method $method = Method::GET;

    public function __construct(string $productId)
    {
        parent::__construct(['product_id' => $productId]);
    }

    public function resolveEndpoint(): string
    {
        return '/v1/products';
    }
}
