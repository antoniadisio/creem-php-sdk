<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Dto\Webhook\WebhookEvent;
use Creem\Exception\InvalidWebhookPayloadException;
use Creem\Exception\InvalidWebhookSignatureException;
use Creem\Exception\TransportException;
use Creem\Webhook;

test('it verifies a known webhook signature after trimming outer header whitespace', function (): void {
    $payload = '{"id":"evt_123","eventType":"license.created","object":{"id":"lic_123"}}';
    $signature = '234b1721e159f683264bc88dd1ec763de794e02f8c8f99762154a68d728e85e1';

    expect(static function () use ($payload, $signature): void {
        Webhook::verifySignature($payload, "  {$signature} \n", 'whsec_test_secret');
    })
        ->not->toThrow(InvalidWebhookSignatureException::class);
});

test('it throws for invalid webhook signatures', function (): void {
    $payload = '{"id":"evt_123","eventType":"license.created","object":{"id":"lic_123"}}';

    expect(static function () use ($payload): void {
        Webhook::verifySignature($payload, 'not-a-valid-signature', 'whsec_test_secret');
    })
        ->toThrow(InvalidWebhookSignatureException::class, 'The Creem webhook signature is invalid.');
});

test('it throws when the webhook secret does not match the signature', function (): void {
    $payload = '{"id":"evt_123","eventType":"license.created","object":{"id":"lic_123"}}';
    $signature = '234b1721e159f683264bc88dd1ec763de794e02f8c8f99762154a68d728e85e1';

    expect(static function () use ($payload, $signature): void {
        Webhook::verifySignature($payload, $signature, 'whsec_wrong_secret');
    })
        ->toThrow(InvalidWebhookSignatureException::class, 'The Creem webhook signature is invalid.');
});

test('it throws for blank webhook signatures', function (): void {
    $payload = '{"id":"evt_123","eventType":"license.created","object":{"id":"lic_123"}}';

    expect(static function () use ($payload): void {
        Webhook::verifySignature($payload, '   ', 'whsec_test_secret');
    })
        ->toThrow(InvalidWebhookSignatureException::class, 'The Creem webhook signature header is missing or blank.');
});

test('it parses webhook events into a stateless wrapper', function (): void {
    $payload = '{"id":"evt_123","eventType":"license.created","object":{"id":"lic_123","active":true}}';

    $event = Webhook::parseEvent($payload);

    expect($event)->toBeInstanceOf(WebhookEvent::class)
        ->and($event->payload()->get('id'))->toBe('evt_123')
        ->and($event->payload()->get('eventType'))->toBe('license.created')
        ->and($event->payload()->get('object'))->toBeInstanceOf(\Creem\Dto\Common\StructuredObject::class)
        ->and($event->toArray()['object'])->toBeInstanceOf(\Creem\Dto\Common\StructuredObject::class);
});

test('it throws webhook payload exceptions for malformed json instead of transport exceptions', function (): void {
    $thrown = null;

    try {
        Webhook::parseEvent('{"id":');
    } catch (\Throwable $exception) {
        $thrown = $exception;
    }

    expect($thrown)->toBeInstanceOf(InvalidWebhookPayloadException::class);
    expect($thrown instanceof TransportException)->toBeFalse();

    if (! $thrown instanceof InvalidWebhookPayloadException) {
        return;
    }

    expect($thrown->getMessage())->toBe('The Creem webhook payload is not valid JSON.');
});

test('it verifies the signature before parsing webhook events', function (): void {
    expect(static fn (): WebhookEvent => Webhook::constructEvent('{"id":', 'invalid', 'whsec_test_secret'))
        ->toThrow(InvalidWebhookSignatureException::class);
});

test('it constructs a verified webhook event without a client instance', function (): void {
    $payload = '{"id":"evt_123","eventType":"license.created","object":{"id":"lic_123"}}';
    $signature = '234b1721e159f683264bc88dd1ec763de794e02f8c8f99762154a68d728e85e1';

    $event = Webhook::constructEvent($payload, $signature, 'whsec_test_secret');

    expect($event)->toBeInstanceOf(WebhookEvent::class)
        ->and($event->payload()->get('id'))->toBe('evt_123');
});
