<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Dto\License\DeactivateLicenseRequest;
use Playground\Support\Playground;

$request = static function (array $values): DeactivateLicenseRequest {
    return new DeactivateLicenseRequest(
        Playground::stringValue(
            Playground::value($values, 'shared.licenseKey'),
            'shared.licenseKey',
        ),
        Playground::stringValue(
            Playground::value($values, 'shared.licenseInstanceId'),
            'shared.licenseInstanceId',
        ),
    );
};

return [
    'resource' => 'licenses',
    'action' => 'deactivate',
    'operation_mode' => 'write',
    'sdk_call' => '$client->licenses()->deactivate(new DeactivateLicenseRequest($licenseKey, $instanceId), $idempotencyKey)',
    'http_method' => 'POST',
    'path' => '/v1/licenses/deactivate',
    'fixtures' => 'license.json',
    'required_values' => [
        'shared.apiKey',
        'shared.licenseKey',
        'shared.licenseInstanceId',
    ],
    'defaults' => [],
    'inputs' => [
        Playground::field('shared.licenseKey', 'License key', 'string'),
        Playground::field('shared.licenseInstanceId', 'Instance ID', 'string'),
        Playground::field('licenses.deactivate.idempotencyKey', 'Idempotency key', 'string'),
    ],
    'idempotency_key_path' => 'licenses.deactivate.idempotencyKey',
    'persist_outputs' => [
        Playground::persist('shared.licenseKey', 'key'),
        Playground::persist('shared.licenseInstanceId', 'instance.id'),
        Playground::persist('shared.licenseInstanceName', 'instance.name'),
    ],
    'build_inputs' => static fn (array $values): array => [
        'licenseKey' => Playground::value($values, 'shared.licenseKey'),
        'instanceId' => Playground::value($values, 'shared.licenseInstanceId'),
        'idempotencyKey' => Playground::value($values, 'licenses.deactivate.idempotencyKey'),
    ],
    'build_request_payload' => static fn (array $values): array => $request($values)->toArray(),
    'run' => static fn (Client $client, array $values) => $client->licenses()->deactivate(
        $request($values),
        Playground::stringValue(
            Playground::value($values, 'licenses.deactivate.idempotencyKey'),
            'licenses.deactivate.idempotencyKey',
        ),
    ),
];
