<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Dto\Product\SearchProductsRequest;
use Playground\Support\Playground;

$request = static function (array $values): SearchProductsRequest {
    return new SearchProductsRequest(
        pageNumber: Playground::value($values, 'products.search.pageNumber'),
        pageSize: Playground::value($values, 'products.search.pageSize'),
    );
};

return [
    'resource' => 'products',
    'action' => 'search',
    'operation_mode' => 'read',
    'sdk_call' => '$client->products()->search(new SearchProductsRequest(...))',
    'http_method' => 'GET',
    'path' => '/v1/products/search',
    'fixtures' => 'product_page.json',
    'required_values' => [
        'shared.apiKey',
    ],
    'defaults' => [
        'products' => [
            'search' => [
                'pageNumber' => 1,
                'pageSize' => 20,
            ],
        ],
    ],
    'inputs' => [
        Playground::field('products.search.pageNumber', 'Page number', 'int'),
        Playground::field('products.search.pageSize', 'Page size', 'int'),
    ],
    'idempotency_key_path' => null,
    'persist_outputs' => [],
    'build_inputs' => static fn (array $values): array => [
        'pageNumber' => Playground::value($values, 'products.search.pageNumber'),
        'pageSize' => Playground::value($values, 'products.search.pageSize'),
    ],
    'build_request_payload' => static fn (array $values): array => $request($values)->toQuery(),
    'run' => static fn (Client $client, array $values) => $client->products()->search($request($values)),
];
