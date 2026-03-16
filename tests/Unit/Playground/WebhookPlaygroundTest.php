<?php

declare(strict_types=1);

use Antoniadisio\Creem\Tests\Support\PlaygroundTestSupport;
use Antoniadisio\Creem\Tests\Support\WebhookTestSupport;
use Playground\Support\WebhookPlayground;

require_once dirname(__DIR__, 3) . '/playground/Support/Playground.php';
require_once dirname(__DIR__, 3) . '/playground/Support/WebhookPlayground.php';

test('webhook playground capture stores verified captures in the override directory', function (): void {
    $tempDir = PlaygroundTestSupport::tempDir('creem-webhook-playground-');
    $workspacePath = PlaygroundTestSupport::playgroundWorkspacePath();
    $environment = webhookPlaygroundEnvironment($tempDir);

    try {
        PlaygroundTestSupport::withEnvironment($environment, function () use ($workspacePath, $environment): void {
            $payload = WebhookTestSupport::eventPayload([
                'eventType' => 'license.created.partner_sync',
                'object' => [
                    'id' => 'sub_fixture_playground',
                    'object' => 'subscription',
                    'status' => 'active',
                    'mode' => 'test',
                ],
            ]);
            $signature = WebhookTestSupport::signatureHeader(
                $payload,
                $environment['CREEM_PLAYGROUND_WEBHOOK_SECRET'],
            );

            /**
/** @var array{
             *     ok: bool,
             *     stored: string,
             *     path: string,
             *     profile: string|null,
             *     verified: bool,
             *     event_type: string|null,
             *     mode_paths: list<array{path: string, value: string}>,
             *     verification_error: string|null,
             *     parse_error: string|null
             * } $capture
             */
            $capture = WebhookPlayground::capture(
                $workspacePath,
                '/creem/webhook',
                ['Creem-Signature' => $signature],
                $payload,
            );
            /** @var list<array<string, mixed>> $captures */
            $captures = WebhookPlayground::inspect($workspacePath);

            expect(WebhookPlayground::captureDirectory($workspacePath))
                ->toBe($environment['CREEM_PLAYGROUND_WEBHOOK_CAPTURE_PATH']);
            expect($capture)->toMatchArray([
                'ok' => true,
                'path' => '/creem/webhook',
                'profile' => 'playground',
                'verified' => true,
                'event_type' => 'license.created.partner_sync',
                'parse_error' => null,
                'verification_error' => null,
            ]);
            expect($capture['mode_paths'])->toContain([
                'path' => 'payload.object.mode',
                'value' => 'test',
            ]);
            expect($captures)->toHaveCount(1);
            expect($captures[0])->toMatchArray([
                'path' => '/creem/webhook',
                'profile' => 'playground',
                'verified' => true,
                'event_id' => 'evt_fixture_subscription_active',
                'event_type' => 'license.created.partner_sync',
                'parse_error' => null,
                'verification_error' => null,
            ]);
            expect(glob($environment['CREEM_PLAYGROUND_WEBHOOK_CAPTURE_PATH'] . '/*.json'))
                ->toHaveCount(1);
        });
    } finally {
        PlaygroundTestSupport::removeDirectory($tempDir);
    }
});

test('webhook playground inspect and health use isolated captures and support profile overrides', function (): void {
    $tempDir = PlaygroundTestSupport::tempDir('creem-webhook-playground-');
    $workspacePath = PlaygroundTestSupport::playgroundWorkspacePath();
    $environment = webhookPlaygroundEnvironment($tempDir);

    try {
        PlaygroundTestSupport::withEnvironment($environment, function () use ($workspacePath, $environment): void {
            $captureDirectory = $environment['CREEM_PLAYGROUND_WEBHOOK_CAPTURE_PATH'];

            if (! mkdir($captureDirectory, 0777, true) && ! is_dir($captureDirectory)) {
                throw new RuntimeException('Unable to create webhook capture directory.');
            }

            $defaultPayload = WebhookTestSupport::eventPayload();
            $playgroundPayload = WebhookTestSupport::eventPayload([
                'id' => 'evt_fixture_playground',
                'eventType' => 'checkout.completed',
            ]);

            PlaygroundTestSupport::writeJsonFile(
                $captureDirectory . '/20260316-120000-aaaa.json',
                webhookCaptureRecord(
                    path: '/',
                    signature: WebhookTestSupport::signatureHeader(
                        $defaultPayload,
                        $environment['CREEM_TEST_WEBHOOK_SECRET'],
                    ),
                    payload: $defaultPayload,
                ),
            );
            PlaygroundTestSupport::writeJsonFile(
                $captureDirectory . '/20260316-120001-bbbb.json',
                webhookCaptureRecord(
                    path: '/creem/webhook',
                    signature: WebhookTestSupport::signatureHeader(
                        $playgroundPayload,
                        $environment['CREEM_PLAYGROUND_WEBHOOK_SECRET'],
                    ),
                    payload: $playgroundPayload,
                    capturedAt: '2026-03-16T12:00:01+00:00',
                ),
            );

            /** @var array<string, mixed> $health */
            $health = WebhookPlayground::captureHealth($workspacePath);
            /** @var list<array<string, mixed>> $limited */
            $limited = WebhookPlayground::inspect($workspacePath, 1);
            /** @var list<array<string, mixed>> $latest */
            $latest = WebhookPlayground::inspect($workspacePath, latestOnly: true);
            /** @var list<array<string, mixed>> $overridden */
            $overridden = WebhookPlayground::inspect($workspacePath, profileName: 'playground');

            expect($health)->toMatchArray([
                'ok' => true,
                'capture_count' => 2,
                'latest_capture' => '20260316-120001-bbbb.json',
                'active_profile' => 'default',
                'secret_configured' => true,
                'profile_error' => null,
            ]);
            expect($limited)->toHaveCount(1);
            expect($limited[0])->toMatchArray([
                'file' => '20260316-120001-bbbb.json',
                'profile' => 'playground',
                'verified' => true,
                'event_id' => 'evt_fixture_playground',
                'event_type' => 'checkout.completed',
            ]);
            expect($latest)->toHaveCount(1);
            expect($latest[0])->toMatchArray([
                'file' => '20260316-120001-bbbb.json',
                'profile' => 'playground',
                'verified' => true,
            ]);
            expect($overridden)->toHaveCount(2);
            expect($overridden[0])->toMatchArray([
                'file' => '20260316-120000-aaaa.json',
                'profile' => 'playground',
                'verified' => false,
                'event_id' => 'evt_fixture_subscription_active',
                'event_type' => 'subscription.active',
            ]);
            expect($overridden[0]['verification_error'])->toContain('InvalidWebhookSignatureException');
            expect($overridden[0]['parse_error'])->toBeNull();
        });
    } finally {
        PlaygroundTestSupport::removeDirectory($tempDir);
    }
});

test('webhook playground analysis falls back to parse-only mode when verification fails', function (): void {
    $tempDir = PlaygroundTestSupport::tempDir('creem-webhook-playground-');
    $workspacePath = PlaygroundTestSupport::playgroundWorkspacePath();
    $environment = webhookPlaygroundEnvironment($tempDir);

    try {
        PlaygroundTestSupport::withEnvironment($environment, function () use ($workspacePath): void {
            $payload = WebhookTestSupport::eventPayload([
                'eventType' => 'license.created.partner_sync',
            ]);

            $analysis = WebhookPlayground::analyze(
                $workspacePath,
                '/creem/webhook',
                ['creem-signature' => 'invalid-signature'],
                $payload,
            );

            expect($analysis)->toMatchArray([
                'profile' => 'playground',
                'verified' => false,
                'event_id' => 'evt_fixture_subscription_active',
                'event_type' => 'license.created.partner_sync',
                'parse_error' => null,
            ]);
            expect($analysis['verification_error'])->toContain('InvalidWebhookSignatureException');
            expect($analysis['payload'])->toBeArray();
        });
    } finally {
        PlaygroundTestSupport::removeDirectory($tempDir);
    }
});

/**
 * @return array<string, string>
 */
function webhookPlaygroundEnvironment(string $tempDir): array
{
    return [
        'CREEM_PLAYGROUND_STATE_PATH' => $tempDir . '/state.local.json',
        'CREEM_PLAYGROUND_STATE_EXAMPLE_PATH' => PlaygroundTestSupport::stateExamplePath(),
        'CREEM_PLAYGROUND_WEBHOOK_CAPTURE_PATH' => $tempDir . '/captures/webhooks',
        'CREEM_PLAYGROUND_API_KEY' => 'sk_test_playground_placeholder',
        'CREEM_TEST_WEBHOOK_SECRET' => 'whsec_test_secret',
        'CREEM_PLAYGROUND_WEBHOOK_SECRET' => 'whsec_playground_secret',
    ];
}

/**
 * @return array<string, mixed>
 */
function webhookCaptureRecord(
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
