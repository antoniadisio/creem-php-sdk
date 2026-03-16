<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Dto\Customer\ListCustomersRequest;
use Playground\Support\Playground;

$request = static function (array $values): ListCustomersRequest {
    return new ListCustomersRequest(
        pageNumber: Playground::value($values, 'customers.list.pageNumber'),
        pageSize: Playground::value($values, 'customers.list.pageSize'),
    );
};

return [
    'resource' => 'customers',
    'action' => 'list',
    'operation_mode' => 'read',
    'sdk_call' => '$client->customers()->list(new ListCustomersRequest(...))',
    'http_method' => 'GET',
    'path' => '/v1/customers/list',
    'fixtures' => 'customer_page.json',
    'required_values' => [
        'shared.apiKey',
    ],
    'defaults' => [
        'customers' => [
            'list' => [
                'pageNumber' => 1,
                'pageSize' => 20,
            ],
        ],
    ],
    'inputs' => [
        Playground::field('customers.list.pageNumber', 'Page number', 'int'),
        Playground::field('customers.list.pageSize', 'Page size', 'int'),
    ],
    'idempotency_key_path' => null,
    'persist_outputs' => [],
    'build_inputs' => static fn (array $values): array => [
        'pageNumber' => Playground::value($values, 'customers.list.pageNumber'),
        'pageSize' => Playground::value($values, 'customers.list.pageSize'),
    ],
    'build_request_payload' => static fn (array $values): array => $request($values)->toQuery(),
    'run' => static fn (Client $client, array $values) => $client->customers()->list($request($values)),
];
