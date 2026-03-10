<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Exception\InvalidWebhookSignatureException;
use Creem\Internal\Webhook\Signature;
use Creem\Tests\Support\WebhookTestSupport;
use Creem\Webhook;

use function time;

test('webhook signature verification accepts a known signature after trimming header whitespace', function (): void {
    $payload = WebhookTestSupport::eventPayload();
    $signature = WebhookTestSupport::timestampedSignatureHeader($payload);

    expect(static function () use ($payload, $signature): void {
        Webhook::verifySignature($payload, "  {$signature} \n", 'whsec_test_secret');
    })->not->toThrow(InvalidWebhookSignatureException::class);
});

test('webhook signature verification rejects invalid signatures', function (): void {
    $payload = WebhookTestSupport::eventPayload();
    $signature = WebhookTestSupport::timestampedSignatureHeader($payload, signature: 'not-a-valid-signature');

    expect(static function () use ($payload, $signature): void {
        Webhook::verifySignature($payload, $signature, 'whsec_test_secret');
    })
        ->toThrow(InvalidWebhookSignatureException::class, 'The Creem webhook signature is invalid.');
});

test('webhook signature verification rejects mismatched secrets', function (): void {
    $payload = WebhookTestSupport::eventPayload();
    $signature = WebhookTestSupport::timestampedSignatureHeader($payload);

    expect(static function () use ($payload, $signature): void {
        Webhook::verifySignature($payload, $signature, 'whsec_wrong_secret');
    })
        ->toThrow(InvalidWebhookSignatureException::class, 'The Creem webhook signature is invalid.');
});

test('webhook signature verification rejects blank secrets', function (): void {
    $payload = WebhookTestSupport::eventPayload();
    $signature = WebhookTestSupport::timestampedSignatureHeader($payload);

    expect(static function () use ($payload, $signature): void {
        Webhook::verifySignature($payload, $signature, '   ');
    })
        ->toThrow(InvalidWebhookSignatureException::class, 'The Creem webhook secret is missing or blank.');
});

test('webhook signature verification rejects timestamps outside the allowed tolerance', function (): void {
    $payload = WebhookTestSupport::eventPayload();
    $signature = WebhookTestSupport::timestampedSignatureHeader($payload, timestamp: time() - 301);

    expect(static function () use ($payload, $signature): void {
        Webhook::verifySignature($payload, $signature, 'whsec_test_secret');
    })
        ->toThrow(InvalidWebhookSignatureException::class, 'The Creem webhook signature timestamp is outside the allowed tolerance.');
});

foreach (invalidWebhookSignatureHeaders() as $dataset => [$signature, $secret, $message]) {
    test("webhook signature verification rejects malformed signature headers ({$dataset})", function () use ($signature, $secret, $message): void {
        $payload = WebhookTestSupport::eventPayload();

        expect(static function () use ($payload, $signature, $secret): void {
            Webhook::verifySignature($payload, $signature, $secret);
        })
            ->toThrow(InvalidWebhookSignatureException::class, $message);
    });
}

/**
 * @return array<string, array{0: string, 1: string, 2: string}>
 */
function invalidWebhookSignatureHeaders(): array
{
    return [
        'blank header' => [
            '   ',
            'whsec_test_secret',
            'The Creem webhook signature header is missing or blank.',
        ],
        'missing timestamp' => [
            Signature::compute(WebhookTestSupport::eventPayload(), 'whsec_test_secret'),
            'whsec_test_secret',
            'The Creem webhook signature timestamp is missing.',
        ],
        'invalid timestamp' => [
            't=invalid,v1=abc123',
            'whsec_test_secret',
            'The Creem webhook signature timestamp is invalid.',
        ],
    ];
}
