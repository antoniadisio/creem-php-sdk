<?php

declare(strict_types=1);

test('playground list returns a JSON catalog of operations', function (): void {
    $result = playgroundRunCli(['--list']);
    $payload = playgroundDecodeJson($result['stdout']);
    $operations = playgroundListValue($payload, 'operations');

    expect($result['exitCode'])->toBe(0)
        ->and($payload)->toMatchArray([
            'ok' => true,
            'kind' => 'operation_list',
        ])
        ->and(array_column($operations, 'operation'))
        ->toContain('products/create', 'stats/summary');
});

test('playground describe returns operation metadata for agents', function (): void {
    $result = playgroundRunCli(['--describe', 'products/create']);
    $payload = playgroundDecodeJson($result['stdout']);
    $persistedOutputs = playgroundListValue($payload, 'persisted_outputs');
    $inputs = playgroundListValue($payload, 'inputs');
    $schemas = playgroundMapValue($payload, 'schemas');

    expect($result['exitCode'])->toBe(0)
        ->and($payload)->toMatchArray([
            'ok' => true,
            'kind' => 'operation_describe',
            'operation' => 'products/create',
            'operation_mode' => 'write',
            'write_requires_allow_write' => true,
        ])
        ->and($persistedOutputs)->toContainEqual([
            'path' => 'shared.productId',
            'source' => 'id',
        ])
        ->and(array_column($inputs, 'path'))->toContain('products.create.idempotencyKey')
        ->and($schemas)->toMatchArray([
            'run_input' => 'playground/schemas/run-input.schema.json',
            'run_output' => 'playground/schemas/run-output.schema.json',
            'operation_describe' => 'playground/schemas/operation-describe.schema.json',
        ]);
});

test('playground run reads JSON from --input-file and bootstraps local state', function (): void {
    $tempDir = playgroundTempDir();
    $statePath = $tempDir . '/state.local.json';
    $inputPath = $tempDir . '/input.json';

    try {
        file_put_contents_or_fail($inputPath, json_encode([
            'profile' => 'default',
            'values' => [
                'stats' => [
                    'summary' => [
                        'currency' => 'INVALID',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $result = playgroundRunCli(
            ['stats/summary', '--input-file', $inputPath],
            null,
            [
                'CREEM_PLAYGROUND_STATE_PATH' => $statePath,
                'CREEM_TEST_API_KEY' => 'creem_test_default_placeholder',
            ],
        );
        $payload = playgroundDecodeJson($result['stdout']);
        $bootstrappedState = playgroundDecodeJson(file_get_contents_or_fail($statePath));
        $error = playgroundMapValue($payload, 'error');
        $sharedState = playgroundMapValue($bootstrappedState, 'shared');
        $profiles = playgroundMapValue($bootstrappedState, 'profiles');
        $playgroundProfile = playgroundMapValue($profiles, 'playground');

        expect($result['exitCode'])->toBe(1)
            ->and($payload)->toMatchArray([
                'ok' => false,
                'kind' => 'operation_result',
                'operation' => 'stats/summary',
                'profile' => 'default',
            ])
            ->and(playgroundStringValue($error, 'message'))->toContain('Invalid enum value for [stats.summary.currency].')
            ->and(file_exists($statePath))->toBeTrue()
            ->and(playgroundStringValue($sharedState, 'activeProfile'))->toBe('default')
            ->and(playgroundStringValue($playgroundProfile, 'apiKeyEnv'))->toBe('CREEM_PLAYGROUND_API_KEY');
    } finally {
        playgroundRemoveDirectory($tempDir);
    }
});

test('playground run reads JSON from stdin and blocks writes without allow_write', function (): void {
    $tempDir = playgroundTempDir();
    $statePath = $tempDir . '/state.local.json';

    try {
        $result = playgroundRunCli(
            ['products/create'],
            json_encode([
                'profile' => 'playground',
                'values' => [
                    'products' => [
                        'create' => [
                            'name' => 'CLI Product',
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            [
                'CREEM_PLAYGROUND_STATE_PATH' => $statePath,
                'CREEM_PLAYGROUND_API_KEY' => 'creem_test_playground_placeholder',
            ],
        );
        $payload = playgroundDecodeJson($result['stdout']);
        $bootstrappedStateJson = file_get_contents_or_fail($statePath);
        $request = playgroundMapValue($payload, 'request');
        $requestPayload = playgroundMapValue($request, 'payload');
        $error = playgroundMapValue($payload, 'error');

        expect($result['exitCode'])->toBe(1)
            ->and($payload)->toMatchArray([
                'ok' => false,
                'kind' => 'operation_result',
                'operation' => 'products/create',
                'profile' => 'playground',
            ])
            ->and(playgroundStringValue($requestPayload, 'name'))->toBe('CLI Product')
            ->and(playgroundStringValue($error, 'message'))->toBe('Write-capable playground operations require input.allow_write=true.')
            ->and($payload['state_changes'])->toBe([])
            ->and($bootstrappedStateJson)->not->toContain('creem_test_playground_placeholder');
    } finally {
        playgroundRemoveDirectory($tempDir);
    }
});

test('playground product create omits billing period for one-time payloads', function (): void {
    $tempDir = playgroundTempDir();
    $statePath = $tempDir . '/state.local.json';

    try {
        $result = playgroundRunCli(
            ['products/create'],
            json_encode([
                'profile' => 'playground',
                'values' => [
                    'products' => [
                        'create' => [
                            'name' => 'CLI One-Time Product',
                            'billingType' => 'onetime',
                            'billingPeriod' => 'once',
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            [
                'CREEM_PLAYGROUND_STATE_PATH' => $statePath,
                'CREEM_PLAYGROUND_API_KEY' => 'creem_test_playground_placeholder',
            ],
        );
        $payload = playgroundDecodeJson($result['stdout']);
        $request = playgroundMapValue($payload, 'request');
        $requestInputs = playgroundMapValue($request, 'inputs');
        $requestPayload = playgroundMapValue($request, 'payload');

        expect($result['exitCode'])->toBe(1)
            ->and(playgroundStringValue($requestInputs, 'billingType'))->toBe('onetime')
            ->and(array_key_exists('billingPeriod', $requestInputs))->toBeTrue()
            ->and($requestInputs['billingPeriod'])->toBeNull()
            ->and(playgroundStringValue($requestPayload, 'billing_type'))->toBe('onetime')
            ->and($requestPayload)->not->toHaveKey('billing_period');
    } finally {
        playgroundRemoveDirectory($tempDir);
    }
});

test('playground audit remains machine readable', function (): void {
    $result = playgroundRunCli(['--audit']);
    $payload = playgroundDecodeJson($result['stdout']);
    $summary = playgroundMapValue($payload, 'summary');

    expect($result['exitCode'])->toBe(0)
        ->and($payload)->toMatchArray([
            'ok' => true,
            'kind' => 'audit',
        ])
        ->and(playgroundIntValue($summary, 'findings'))->toBe(0);
});

/**
 * @param  list<string>  $arguments
 * @param  array<string, string>  $env
 * @return array{exitCode: int, stdout: string, stderr: string}
 */
function playgroundRunCli(array $arguments, ?string $stdin = null, array $env = []): array
{
    $repoRoot = dirname(__DIR__, 3);
    $command = array_merge([PHP_BINARY, $repoRoot . '/playground/run.php'], $arguments);
    /** @var array<string, string> $environment */
    $environment = getenv();
    $process = proc_open(
        $command,
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $repoRoot,
        array_merge($environment, $env),
    );

    if (! is_resource($process)) {
        throw new RuntimeException('Unable to start playground CLI process.');
    }

    if ($stdin !== null) {
        fwrite($pipes[0], $stdin);
    }

    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    return [
        'exitCode' => proc_close($process),
        'stdout' => is_string($stdout) ? $stdout : '',
        'stderr' => is_string($stderr) ? $stderr : '',
    ];
}

/**
 * @return array<string, mixed>
 */
function playgroundDecodeJson(string $json): array
{
    /** @var array<string, mixed> $decoded */
    $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

    return $decoded;
}

/**
 * @param  array<string, mixed>  $payload
 * @return list<array<string, mixed>>
 */
function playgroundListValue(array $payload, string $key): array
{
    $value = $payload[$key] ?? null;

    if (! is_array($value) || ! array_is_list($value)) {
        throw new RuntimeException(sprintf('Expected [%s] to be a JSON array.', $key));
    }

    foreach ($value as $item) {
        if (! is_array($item)) {
            throw new RuntimeException(sprintf('Expected [%s] to contain JSON objects.', $key));
        }
    }

    /** @var list<array<string, mixed>> $value */
    return $value;
}

/**
 * @param  array<string, mixed>  $payload
 * @return array<string, mixed>
 */
function playgroundMapValue(array $payload, string $key): array
{
    $value = $payload[$key] ?? null;

    if (! is_array($value) || array_is_list($value)) {
        throw new RuntimeException(sprintf('Expected [%s] to be a JSON object.', $key));
    }

    /** @var array<string, mixed> $value */
    return $value;
}

/**
 * @param  array<string, mixed>  $payload
 */
function playgroundStringValue(array $payload, string $key): string
{
    $value = $payload[$key] ?? null;

    if (! is_string($value)) {
        throw new RuntimeException(sprintf('Expected [%s] to be a string.', $key));
    }

    return $value;
}

/**
 * @param  array<string, mixed>  $payload
 */
function playgroundIntValue(array $payload, string $key): int
{
    $value = $payload[$key] ?? null;

    if (! is_int($value)) {
        throw new RuntimeException(sprintf('Expected [%s] to be an int.', $key));
    }

    return $value;
}

function playgroundTempDir(): string
{
    $path = sys_get_temp_dir() . '/creem-playground-' . bin2hex(random_bytes(8));

    if (! mkdir($path, 0777, true) && ! file_exists($path)) {
        throw new RuntimeException('Unable to create playground temp directory.');
    }

    return $path;
}

function playgroundRemoveDirectory(string $path): void
{
    if (! file_exists($path)) {
        return;
    }

    $entries = scandir($path);

    if (! is_array($entries)) {
        throw new RuntimeException('Unable to scan playground temp directory.');
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $entryPath = $path . '/' . $entry;

        if (is_dir($entryPath)) {
            playgroundRemoveDirectory($entryPath);

            continue;
        }

        unlink($entryPath);
    }

    rmdir($path);
}

function file_put_contents_or_fail(string $path, string $contents): void
{
    if (file_put_contents($path, $contents) === false) {
        throw new RuntimeException(sprintf('Unable to write [%s].', $path));
    }
}

function file_get_contents_or_fail(string $path): string
{
    $contents = file_get_contents($path);

    if (! is_string($contents)) {
        throw new RuntimeException(sprintf('Unable to read [%s].', $path));
    }

    return $contents;
}
