<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Dto\Customer\CreateCustomerBillingPortalLinkRequest;
use Playground\Support\Playground;

$request = static function (array $values): CreateCustomerBillingPortalLinkRequest {
    return new CreateCustomerBillingPortalLinkRequest(
        Playground::stringValue(
            Playground::value($values, 'shared.customerId'),
            'shared.customerId',
        ),
    );
};

return [
    'resource' => 'customers',
    'action' => 'create_billing_portal_link',
    'operation_mode' => 'write',
    'sdk_call' => '$client->customers()->createBillingPortalLink(new CreateCustomerBillingPortalLinkRequest($customerId), $idempotencyKey)',
    'http_method' => 'POST',
    'path' => '/v1/customers/billing',
    'fixtures' => 'customer_links.json',
    'required_values' => [
        'shared.apiKey',
        'shared.customerId',
    ],
    'defaults' => [],
    'inputs' => [
        Playground::field('shared.customerId', 'Customer ID', 'string'),
        Playground::field('customers.create_billing_portal_link.idempotencyKey', 'Idempotency key', 'string'),
    ],
    'idempotency_key_path' => 'customers.create_billing_portal_link.idempotencyKey',
    'persist_outputs' => [],
    'build_inputs' => static fn (array $values): array => [
        'customerId' => Playground::value($values, 'shared.customerId'),
        'idempotencyKey' => Playground::value($values, 'customers.create_billing_portal_link.idempotencyKey'),
    ],
    'build_request_payload' => static fn (array $values): array => $request($values)->toArray(),
    'run' => static fn (Client $client, array $values) => $client->customers()->createBillingPortalLink(
        $request($values),
        Playground::stringValue(
            Playground::value($values, 'customers.create_billing_portal_link.idempotencyKey'),
            'customers.create_billing_portal_link.idempotencyKey',
        ),
    ),
];
