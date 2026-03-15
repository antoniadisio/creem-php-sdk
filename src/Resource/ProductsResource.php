<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Resource;

use Antoniadisio\Creem\Dto\Common\Page;
use Antoniadisio\Creem\Dto\Product\CreateProductRequest;
use Antoniadisio\Creem\Dto\Product\Product;
use Antoniadisio\Creem\Dto\Product\SearchProductsRequest;
use Antoniadisio\Creem\Internal\Http\Requests\Products\CreateProductRequest as CreateProductOperation;
use Antoniadisio\Creem\Internal\Http\Requests\Products\RetrieveProductRequest;
use Antoniadisio\Creem\Internal\Http\Requests\Products\SearchProductsRequest as SearchProductsOperation;
use Antoniadisio\Creem\Internal\Hydration\Payload;

final class ProductsResource extends Resource
{
    public function get(string $id): Product
    {
        return Product::fromPayload($this->send(new RetrieveProductRequest($id)));
    }

    public function create(CreateProductRequest $request, ?string $idempotencyKey = null): Product
    {
        return Product::fromPayload($this->send(new CreateProductOperation($request->toArray(), $idempotencyKey)));
    }

    /**
     * @return Page<Product>
     */
    public function search(?SearchProductsRequest $request = null): Page
    {
        $request ??= new SearchProductsRequest;

        return Payload::page(
            $this->send(new SearchProductsOperation($request->toQuery())),
            static fn (array $item): Product => Product::fromPayload($item),
        );
    }
}
