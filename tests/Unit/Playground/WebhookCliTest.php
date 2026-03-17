<?php

declare(strict_types=1);

use Antoniadisio\Creem\Tests\Support\PlaygroundTestSupport;
use Antoniadisio\Creem\Tests\Support\WebhookTestSupport;

test('webhook receive entrypoint serves health and captures routed deliveries', function (): void {
    $tempDir = PlaygroundTestSupport::tempDir('creem-webhook-cli-');
    $environment = webhookCliEnvironment($tempDir);

    try {
        $health = webhookReceiveRequest($environment, 'GET', '/health');
        $payload = WebhookTestSupport::eventPayload([
            'eventType' => 'license.created.partner_sync',
        ]);
        $signature = WebhookTestSupport::signatureHeader(
            $payload,
            $environment['CREEM_PLAYGROUND_WEBHOOK_SECRET'],
        );
        $capture = webhookReceiveRequest(
            $environment,
            'POST',
            '/creem/webhook',
            $payload,
            ['Creem-Signature' => $signature],
        );

        expect($health['exitCode'])->toBe(0);
        expect($health['status'])->toBe(200);
        expect($health['json'])->toMatchArray([
            'ok' => true,
            'capture_count' => 0,
            'active_profile' => 'default',
            'secret_configured' => true,
            'profile_error' => null,
        ]);
        expect($capture['exitCode'])->toBe(0);
        expect($capture['status'])->toBe(200);
        expect($capture['json'])->toMatchArray([
            'ok' => true,
            'path' => '/creem/webhook',
            'profile' => 'playground',
            'verified' => true,
            'event_type' => 'license.created.partner_sync',
            'parse_error' => null,
            'verification_error' => null,
        ]);
    } finally {
        PlaygroundTestSupport::removeDirectory($tempDir);
    }
});

test('webhook receive entrypoint rejects unsupported methods', function (): void {
    $tempDir = PlaygroundTestSupport::tempDir('creem-webhook-cli-');
    $environment = webhookCliEnvironment($tempDir);

    try {
        $response = webhookReceiveRequest($environment, 'PUT', '/creem/webhook');

        expect($response['exitCode'])->toBe(0);
        expect($response['status'])->toBe(405);
        expect($response['json'])->toMatchArray([
            'ok' => false,
            'error' => 'Method not allowed.',
            'method' => 'PUT',
            'path' => '/creem/webhook',
        ]);
    } finally {
        PlaygroundTestSupport::removeDirectory($tempDir);
    }
});

test('webhook inspect entrypoint supports latest limit and profile overrides', function (): void {
    $tempDir = PlaygroundTestSupport::tempDir('creem-webhook-cli-');
    $environment = webhookCliEnvironment($tempDir);
    $captureDirectory = $environment['CREEM_PLAYGROUND_WEBHOOK_CAPTURE_PATH'];

    try {
        if (! mkdir($captureDirectory, 0777, true) && ! is_dir($captureDirectory)) {
            throw new RuntimeException('Unable to create webhook capture directory.');
        }

        $playgroundPayload = WebhookTestSupport::eventPayload([
            'id' => 'evt_fixture_playground',
            'eventType' => 'checkout.completed',
        ]);
        $defaultPayload = WebhookTestSupport::eventPayload();

        PlaygroundTestSupport::writeJsonFile(
            $captureDirectory . '/20260316-120000-aaaa.json',
            webhookCliCaptureRecord(
                path: '/creem/webhook',
                signature: WebhookTestSupport::signatureHeader(
                    $playgroundPayload,
                    $environment['CREEM_PLAYGROUND_WEBHOOK_SECRET'],
                ),
                payload: $playgroundPayload,
            ),
        );
        PlaygroundTestSupport::writeJsonFile(
            $captureDirectory . '/20260316-120001-bbbb.json',
            webhookCliCaptureRecord(
                path: '/',
                signature: WebhookTestSupport::signatureHeader(
                    $defaultPayload,
                    $environment['CREEM_TEST_WEBHOOK_SECRET'],
                ),
                payload: $defaultPayload,
                capturedAt: '2026-03-16T12:00:01+00:00',
            ),
        );

        $script = PlaygroundTestSupport::playgroundWorkspacePath() . '/webhooks/inspect.php';
        $latest = PlaygroundTestSupport::runPhpScript($script, ['--latest'], env: $environment);
        $limited = PlaygroundTestSupport::runPhpScript($script, ['--limit', '1'], env: $environment);
        $overridden = PlaygroundTestSupport::runPhpScript(
            $script,
            ['--latest', '--profile', 'playground'],
            env: $environment,
        );

        $latestJson = PlaygroundTestSupport::decodeJsonObject($latest['stdout']);
        $limitedJson = PlaygroundTestSupport::decodeJsonObject($limited['stdout']);
        $overriddenJson = PlaygroundTestSupport::decodeJsonObject($overridden['stdout']);
        $latestCaptures = webhookCliCaptureList($latestJson);
        $limitedCaptures = webhookCliCaptureList($limitedJson);
        $overriddenCaptures = webhookCliCaptureList($overriddenJson);

        expect($latest['exitCode'])->toBe(0);
        expect($limited['exitCode'])->toBe(0);
        expect($overridden['exitCode'])->toBe(0);
        expect($latestCaptures[0])->toMatchArray([
            'file' => '20260316-120001-bbbb.json',
            'profile' => 'default',
            'verified' => true,
            'event_id' => 'evt_fixture_subscription_active',
            'event_type' => 'subscription.active',
        ]);
        expect($limitedCaptures[0])->toMatchArray([
            'file' => '20260316-120001-bbbb.json',
            'profile' => 'default',
            'verified' => true,
        ]);
        expect($overriddenCaptures[0])->toMatchArray([
            'file' => '20260316-120001-bbbb.json',
            'profile' => 'playground',
            'verified' => false,
            'event_id' => 'evt_fixture_subscription_active',
            'event_type' => 'subscription.active',
        ]);
        expect($overriddenCaptures[0]['verification_error'])->toContain('InvalidWebhookSignatureException');
    } finally {
        PlaygroundTestSupport::removeDirectory($tempDir);
    }
});

test('webhook inspect entrypoint fails cleanly on invalid options', function (): void {
    $tempDir = PlaygroundTestSupport::tempDir('creem-webhook-cli-');
    $environment = webhookCliEnvironment($tempDir);
    $script = PlaygroundTestSupport::playgroundWorkspacePath() . '/webhooks/inspect.php';

    try {
        $result = PlaygroundTestSupport::runPhpScript($script, ['--bogus'], env: $environment);

        expect($result['exitCode'])->toBe(1);
        expect($result['stdout'])->toBe('');
        expect($result['stderr'])->toContain('Unknown option [--bogus].');
    } finally {
        PlaygroundTestSupport::removeDirectory($tempDir);
    }
});

/**
 * @param  array<string, string>  $environment
 * @param  array<string, string>  $headers
 * @return array{exitCode: int, status: int, json: array<string, mixed>, stderr: string}
 */
function webhookReceiveRequest(
    array $environment,
    string $method,
    string $path,
    string $payload = '',
    array $headers = [],
): array {
    $cgiEnvironment = [
        'REQUEST_METHOD' => $method,
        'REQUEST_URI' => $path,
        'SCRIPT_FILENAME' => PlaygroundTestSupport::playgroundWorkspacePath() . '/webhooks/receive.php',
        'REDIRECT_STATUS' => '1',
        'CONTENT_LENGTH' => (string) strlen($payload),
    ];

    if ($payload !== '') {
        $cgiEnvironment['CONTENT_TYPE'] = 'application/json';
    }

    foreach ($headers as $name => $value) {
        $cgiEnvironment['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
    }

    $payloadFile = null;
    $commandEnvironment = array_merge($environment, $cgiEnvironment);

    if ($payload !== '') {
        $payloadFile = PlaygroundTestSupport::tempDir('creem-webhook-input-') . '/payload.json';
        PlaygroundTestSupport::writeFile($payloadFile, $payload);
        $commandEnvironment['CREEM_PLAYGROUND_WEBHOOK_INPUT_FILE'] = $payloadFile;
    }

    try {
        $result = PlaygroundTestSupport::runCommand(
            [
                PHP_BINARY,
                PlaygroundTestSupport::playgroundWorkspacePath() . '/webhooks/receive.php',
            ],
            null,
            $commandEnvironment,
        );
        $response = parseCliJsonResponse($result['stdout']);
    } finally {
        if ($payloadFile !== null) {
            PlaygroundTestSupport::removeDirectory(dirname($payloadFile));
        }
    }

    return [
        'exitCode' => $result['exitCode'],
        'status' => $response['status'],
        'json' => $response['json'],
        'stderr' => $result['stderr'],
    ];
}

/**
 * @return array{status: int, json: array<string, mixed>}
 */
function parseCliJsonResponse(string $output): array
{
    $status = 200;

    if (preg_match('/^\s*Status:\s+(\d{3})\b/m', $output, $matches) === 1) {
        $status = (int) $matches[1];
    }

    if (preg_match('/\{.*\}\s*$/s', $output, $matches) !== 1) {
        throw new RuntimeException('Unable to extract JSON body from CLI response.');
    }

    $body = $matches[0];
    $json = PlaygroundTestSupport::decodeJsonObject($body);

    if (
        $status === 200
        && ($json['ok'] ?? null) === false
        && ($json['error'] ?? null) === 'Method not allowed.'
    ) {
        $status = 405;
    }

    return [
        'status' => $status,
        'json' => $json,
    ];
}

/**
 * @param  array<string, mixed>  $payload
 * @return list<array<string, mixed>>
 */
function webhookCliCaptureList(array $payload): array
{
    $captures = $payload['captures'] ?? null;

    if (! is_array($captures) || ! array_is_list($captures)) {
        throw new RuntimeException('Expected [captures] to be a JSON array.');
    }

    /** @var list<array<string, mixed>> $captures */
    return $captures;
}

/**
 * @return array<string, string>
 */
function webhookCliEnvironment(string $tempDir): array
{
    return [
        'CREEM_PLAYGROUND_STATE_PATH' => $tempDir . '/state.local.json',
        'CREEM_PLAYGROUND_STATE_EXAMPLE_PATH' => PlaygroundTestSupport::stateExamplePath(),
        'CREEM_PLAYGROUND_WEBHOOK_CAPTURE_PATH' => $tempDir . '/captures/webhooks',
        'CREEM_PLAYGROUND_API_KEY' => 'creem_test_playground_placeholder',
        'CREEM_TEST_WEBHOOK_SECRET' => 'whsec_test_secret',
        'CREEM_PLAYGROUND_WEBHOOK_SECRET' => 'whsec_playground_secret',
    ];
}

/**
 * @return array<string, mixed>
 */
function webhookCliCaptureRecord(
    string $path,
    string $signature,
    string $payload,
    string $capturedAt = '2026-03-16T12:00:00+00:00',
): array {
    return [
        'captured_at' => $capturedAt,
        'path' => $path,
        'headers' => [
            'creem-signature' => $signature,
        ],
        'payload' => PlaygroundTestSupport::decodeJsonObject($payload),
        'raw_payload' => $payload,
    ];
}
