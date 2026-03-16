<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\CredentialProfile;
use Antoniadisio\Creem\CredentialProfiles;
use Antoniadisio\Creem\Dto\Webhook\WebhookEvent;
use Antoniadisio\Creem\Exception\InvalidWebhookSignatureException;
use Antoniadisio\Creem\Tests\Support\WebhookTestSupport;
use Antoniadisio\Creem\Webhook;
use InvalidArgumentException;

test('webhook profile verification accepts the configured profile secret', function (): void {
    $payload = WebhookTestSupport::eventPayload();
    $signature = WebhookTestSupport::signatureHeader($payload, 'whsec_default_secret');
    $profiles = new CredentialProfiles([
        'default' => new CredentialProfile(
            apiKey: 'sk_test_123',
            webhookSecret: 'whsec_default_secret',
        ),
    ]);

    expect(static function () use ($payload, $signature, $profiles): void {
        Webhook::verifySignatureForProfile($payload, $signature, 'default', $profiles);
    })->not->toThrow(InvalidWebhookSignatureException::class);
});

test('webhook profile verification rejects unknown profiles', function (): void {
    $payload = WebhookTestSupport::eventPayload();
    $signature = WebhookTestSupport::signatureHeader($payload, 'whsec_default_secret');
    $profiles = new CredentialProfiles([
        'default' => new CredentialProfile(
            apiKey: 'sk_test_123',
            webhookSecret: 'whsec_default_secret',
        ),
    ]);

    expect(static function () use ($payload, $signature, $profiles): void {
        Webhook::verifySignatureForProfile($payload, $signature, 'missing', $profiles);
    })->toThrow(InvalidArgumentException::class, 'Unknown Creem credential profile [missing].');
});

test('webhook profile verification rejects profiles without secrets', function (): void {
    $payload = WebhookTestSupport::eventPayload();
    $signature = WebhookTestSupport::signatureHeader($payload, 'whsec_default_secret');
    $profiles = new CredentialProfiles([
        'default' => new CredentialProfile(
            apiKey: 'sk_test_123',
        ),
    ]);

    expect(static function () use ($payload, $signature, $profiles): void {
        Webhook::verifySignatureForProfile($payload, $signature, 'default', $profiles);
    })->toThrow(InvalidArgumentException::class, 'The Creem credential profile [default] does not define a webhook secret.');
});

test('webhook construction builds verified events for named profiles', function (): void {
    $payload = WebhookTestSupport::eventPayload();
    $signature = WebhookTestSupport::signatureHeader($payload, 'whsec_default_secret');
    $profiles = new CredentialProfiles([
        'default' => new CredentialProfile(
            apiKey: 'sk_test_123',
            webhookSecret: 'whsec_default_secret',
        ),
    ]);

    $event = Webhook::constructEventForProfile($payload, $signature, 'default', $profiles);

    expect($event)->toBeInstanceOf(WebhookEvent::class)
        ->and($event->id())->toBe('evt_fixture_subscription_active');
});
