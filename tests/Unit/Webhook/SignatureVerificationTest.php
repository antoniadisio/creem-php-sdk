<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Exception\InvalidWebhookSignatureException;
use Antoniadisio\Creem\Tests\Support\WebhookTestSupport;
use Antoniadisio\Creem\Webhook;

test('webhook signature verification accepts a known signature after trimming header whitespace', function (): void {
    $payload = WebhookTestSupport::eventPayload();
    $signature = WebhookTestSupport::signatureHeader($payload);

    expect(static function () use ($payload, $signature): void {
        Webhook::verifySignature($payload, "  {$signature} \n", 'whsec_test_secret');
    })->not->toThrow(InvalidWebhookSignatureException::class);
});

test('webhook signature verification rejects invalid signatures', function (): void {
    $payload = WebhookTestSupport::eventPayload();
    $signature = WebhookTestSupport::signatureHeader($payload, signature: 'not-a-valid-signature');

    expect(static function () use ($payload, $signature): void {
        Webhook::verifySignature($payload, $signature, 'whsec_test_secret');
    })
        ->toThrow(InvalidWebhookSignatureException::class, 'The Creem webhook signature is invalid.');
});

test('webhook signature verification rejects mismatched secrets', function (): void {
    $payload = WebhookTestSupport::eventPayload();
    $signature = WebhookTestSupport::signatureHeader($payload);

    expect(static function () use ($payload, $signature): void {
        Webhook::verifySignature($payload, $signature, 'whsec_wrong_secret');
    })
        ->toThrow(InvalidWebhookSignatureException::class, 'The Creem webhook signature is invalid.');
});

test('webhook signature verification rejects blank secrets', function (): void {
    $payload = WebhookTestSupport::eventPayload();
    $signature = WebhookTestSupport::signatureHeader($payload);

    expect(static function () use ($payload, $signature): void {
        Webhook::verifySignature($payload, $signature, '   ');
    })
        ->toThrow(InvalidWebhookSignatureException::class, 'The Creem webhook secret is missing or blank.');
});

test('webhook signature verification rejects legacy timestamped headers', function (): void {
    $payload = WebhookTestSupport::eventPayload();
    $signature = 't=1700000000,v1='.WebhookTestSupport::signatureHeader($payload);

    expect(static function () use ($payload, $signature): void {
        Webhook::verifySignature($payload, $signature, 'whsec_test_secret');
    })
        ->toThrow(InvalidWebhookSignatureException::class, 'The Creem webhook signature is invalid.');
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
        'legacy header with invalid digest' => [
            't=invalid,v1=abc123',
            'whsec_test_secret',
            'The Creem webhook signature is invalid.',
        ],
    ];
}
