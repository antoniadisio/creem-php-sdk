<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Dto\License\ActivateLicenseRequest;
use Playground\Support\Playground;

$request = static function (array $values): ActivateLicenseRequest {
    return new ActivateLicenseRequest(
        Playground::stringValue(
            Playground::value($values, 'shared.licenseKey'),
            'shared.licenseKey',
        ),
        Playground::stringValue(
            Playground::value($values, 'shared.licenseInstanceName'),
            'shared.licenseInstanceName',
        ),
    );
};

return [
    'resource' => 'licenses',
    'action' => 'activate',
    'operation_mode' => 'write',
    'sdk_call' => '$client->licenses()->activate(new ActivateLicenseRequest($licenseKey, $instanceName), $idempotencyKey)',
    'http_method' => 'POST',
    'path' => '/v1/licenses/activate',
    'fixtures' => 'license.json',
    'required_values' => [
        'shared.apiKey',
        'shared.licenseKey',
        'shared.licenseInstanceName',
    ],
    'defaults' => [],
    'inputs' => [
        Playground::field('shared.licenseKey', 'License key', 'string'),
        Playground::field('shared.licenseInstanceName', 'Instance name', 'string'),
        Playground::field('licenses.activate.idempotencyKey', 'Idempotency key', 'string'),
    ],
    'idempotency_key_path' => 'licenses.activate.idempotencyKey',
    'persist_outputs' => [
        Playground::persist('shared.licenseKey', 'key'),
        Playground::persist('shared.licenseInstanceId', 'instance.id'),
        Playground::persist('shared.licenseInstanceName', 'instance.name'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'licenseKey' => Playground::value($values, 'shared.licenseKey'),
        'instanceName' => Playground::value($values, 'shared.licenseInstanceName'),
        'idempotencyKey' => Playground::value($values, 'licenses.activate.idempotencyKey'),
    ],
    'build_request_payload' => static fn (array $values): array => $request($values)->toArray(),
    'run' => static fn (Client $client, array $values) => $client->licenses()->activate(
        $request($values),
        Playground::stringValue(
            Playground::value($values, 'licenses.activate.idempotencyKey'),
            'licenses.activate.idempotencyKey',
        ),
    ),
];
