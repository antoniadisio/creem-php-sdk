<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Dto\Transaction\SearchTransactionsRequest;
use Playground\Support\Playground;

$request = static function (array $values): SearchTransactionsRequest {
    return new SearchTransactionsRequest(
        customerId: Playground::nullableString(
            Playground::value($values, 'transactions.search.customerId'),
        ),
        orderId: Playground::nullableString(
            Playground::value($values, 'transactions.search.orderId'),
        ),
        productId: Playground::nullableString(
            Playground::value($values, 'transactions.search.productId'),
        ),
        pageNumber: Playground::value($values, 'transactions.search.pageNumber'),
        pageSize: Playground::value($values, 'transactions.search.pageSize'),
    );
};

return [
    'resource' => 'transactions',
    'action' => 'search',
    'operation_mode' => 'read',
    'sdk_call' => '$client->transactions()->search(new SearchTransactionsRequest(...))',
    'http_method' => 'GET',
    'path' => '/v1/transactions/search',
    'fixtures' => 'transaction_page.json',
    'required_values' => [
        'shared.apiKey',
    ],
    'defaults' => [
        'transactions' => [
            'search' => [
                'customerId' => null,
                'orderId' => null,
                'productId' => null,
                'pageNumber' => 1,
                'pageSize' => 25,
            ],
        ],
    ],
    'inputs' => [
        Playground::field('transactions.search.customerId', 'Customer ID', 'nullable-string', nullable: true),
        Playground::field('transactions.search.orderId', 'Order ID', 'nullable-string', nullable: true),
        Playground::field('transactions.search.productId', 'Product ID', 'nullable-string', nullable: true),
        Playground::field('transactions.search.pageNumber', 'Page number', 'int'),
        Playground::field('transactions.search.pageSize', 'Page size', 'int'),
    ],
    'idempotency_key_path' => null,
    'persist_outputs' => [],
    'build_inputs' => static fn (array $values): array => [
        'customerId' => Playground::value($values, 'transactions.search.customerId'),
        'orderId' => Playground::value($values, 'transactions.search.orderId'),
        'productId' => Playground::value($values, 'transactions.search.productId'),
        'pageNumber' => Playground::value($values, 'transactions.search.pageNumber'),
        'pageSize' => Playground::value($values, 'transactions.search.pageSize'),
    ],
    'build_request_payload' => static fn (array $values): array => $request($values)->toQuery(),
    'run' => static fn (Client $client, array $values) => $client->transactions()->search($request($values)),
];
