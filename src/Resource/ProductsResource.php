<?php

declare(strict_types=1);

namespace Creem\Resource;

use Creem\Dto\Common\Page;
use Creem\Dto\Product\CreateProductRequest;
use Creem\Dto\Product\Product;
use Creem\Dto\Product\SearchProductsRequest;
use Creem\Internal\Http\Requests\Products\CreateProductRequest as CreateProductOperation;
use Creem\Internal\Http\Requests\Products\RetrieveProductRequest;
use Creem\Internal\Http\Requests\Products\SearchProductsRequest as SearchProductsOperation;
use Creem\Internal\Hydration\Payload;

final class ProductsResource extends Resource
{
    public function get(string $id): Product
    {
        return Product::fromPayload($this->send(new RetrieveProductRequest($id)));
    }

    public function create(CreateProductRequest $request): Product
    {
        return Product::fromPayload($this->send(new CreateProductOperation($request->toArray())));
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
