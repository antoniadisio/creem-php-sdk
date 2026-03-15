<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Dto\Common\StructuredObject;
use Antoniadisio\Creem\Dto\Webhook\WebhookEvent;
use Antoniadisio\Creem\Exception\HydrationException;
use Antoniadisio\Creem\Exception\InvalidWebhookPayloadException;
use Antoniadisio\Creem\Exception\InvalidWebhookSignatureException;
use Antoniadisio\Creem\Exception\TransportException;
use Antoniadisio\Creem\Tests\Support\WebhookTestSupport;
use Antoniadisio\Creem\Webhook;

use function str_repeat;

test('webhook event parsing hydrates documented envelopes into typed wrappers', function (): void {
    $payload = WebhookTestSupport::eventPayload([
        'object' => [
            'active' => true,
        ],
    ]);

    $event = Webhook::parseEvent($payload);

    expect($event)->toBeInstanceOf(WebhookEvent::class)
        ->and($event->id())->toBe('evt_fixture_license_created')
        ->and($event->eventType())->toBe('license.created')
        ->and($event->createdAt()->format(DATE_ATOM))->toBe('2026-03-07T06:49:26+00:00')
        ->and($event->object())->toBeInstanceOf(StructuredObject::class)
        ->and($event->object()->get('id'))->toBe('lk_fixture_primary')
        ->and($event->payload()->get('object'))->toBeInstanceOf(StructuredObject::class)
        ->and($event->toArray()['object'])->toBeInstanceOf(StructuredObject::class);
});

test('webhook event parsing keeps unknown event types as raw strings', function (): void {
    $payload = WebhookTestSupport::eventPayload([
        'eventType' => 'license.created.partner_sync',
    ]);

    $event = Webhook::parseEvent($payload);

    expect($event->eventType())->toBe('license.created.partner_sync');
});

test('webhook event parsing accepts subscription scheduled cancel events without special cases', function (): void {
    $payload = WebhookTestSupport::eventPayload([
        'id' => 'evt_fixture_subscription_scheduled_cancel',
        'eventType' => 'subscription.scheduled_cancel',
        'object' => [
            'id' => 'sub_fixture_primary',
            'object' => 'subscription',
            'status' => 'scheduled_cancel',
        ],
    ]);

    $event = Webhook::parseEvent($payload);

    expect($event->eventType())->toBe('subscription.scheduled_cancel')
        ->and($event->object()->get('id'))->toBe('sub_fixture_primary')
        ->and($event->object()->get('status'))->toBe('scheduled_cancel');
});

test('webhook event parsing accepts epoch timestamps from live deliveries', function (): void {
    $payload = WebhookTestSupport::eventPayload([
        'created_at' => 1773422218069,
    ]);

    $event = Webhook::parseEvent($payload);

    expect($event->createdAt()->format('Y-m-d\\TH:i:s.vP'))->toBe('2026-03-13T17:16:58.000+00:00');
});

test('webhook event parsing throws payload exceptions for malformed json instead of transport exceptions', function (): void {
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

test('webhook event parsing rejects payloads that exceed the size limit before decoding', function (): void {
    $payload = str_repeat('a', 1_048_577);

    expect(static fn (): WebhookEvent => Webhook::parseEvent($payload))
        ->toThrow(InvalidWebhookPayloadException::class, 'The Creem webhook payload exceeds the 1048576 byte limit.');
});

test('webhook event parsing rejects payloads with missing envelope fields', function (): void {
    $payload = WebhookTestSupport::eventPayloadArray();
    unset($payload['eventType']);

    expect(static fn (): WebhookEvent => Webhook::parseEvent(WebhookTestSupport::encodePayload($payload)))
        ->toThrow(InvalidWebhookPayloadException::class, 'The Creem webhook payload is not a valid event object.');
});

test('webhook event parsing preserves hydration failures for malformed envelope fields', function (): void {
    $thrown = null;

    try {
        $payload = WebhookTestSupport::eventPayloadArray();
        $payload['object'] = [];

        Webhook::parseEvent(WebhookTestSupport::encodePayload($payload));
    } catch (\Throwable $exception) {
        $thrown = $exception;
    }

    expect($thrown)->toBeInstanceOf(InvalidWebhookPayloadException::class);

    if (! $thrown instanceof InvalidWebhookPayloadException) {
        return;
    }

    expect($thrown->getPrevious())->toBeInstanceOf(HydrationException::class);
});

test('webhook construction verifies signatures before parsing events', function (): void {
    $payload = '{"id":';
    $signature = WebhookTestSupport::signatureHeader($payload, signature: 'invalid');

    expect(static fn (): WebhookEvent => Webhook::constructEvent($payload, $signature, 'whsec_test_secret'))
        ->toThrow(InvalidWebhookSignatureException::class);
});

test('webhook construction throws payload exceptions for malformed verified payloads', function (): void {
    $payload = '{"id":';
    $signature = WebhookTestSupport::signatureHeader($payload);

    expect(static fn (): WebhookEvent => Webhook::constructEvent($payload, $signature, 'whsec_test_secret'))
        ->toThrow(InvalidWebhookPayloadException::class, 'The Creem webhook payload is not valid JSON.');
});

test('webhook construction builds verified events without a client instance', function (): void {
    $payload = WebhookTestSupport::eventPayload();
    $signature = WebhookTestSupport::signatureHeader($payload);

    $event = Webhook::constructEvent($payload, $signature, 'whsec_test_secret');

    expect($event)->toBeInstanceOf(WebhookEvent::class)
        ->and($event->id())->toBe('evt_fixture_license_created')
        ->and($event->object()->get('id'))->toBe('lk_fixture_primary');
});

test('webhook construction rejects replayed events when the replay callback returns true', function (): void {
    $payload = WebhookTestSupport::eventPayload();
    $signature = WebhookTestSupport::signatureHeader($payload);
    $receivedEventId = null;

    expect(static function () use ($payload, $signature, &$receivedEventId): void {
        Webhook::constructEvent(
            $payload,
            $signature,
            'whsec_test_secret',
            static function (WebhookEvent $event) use (&$receivedEventId): bool {
                $receivedEventId = $event->id();

                return true;
            },
        );
    })
        ->toThrow(InvalidWebhookSignatureException::class, 'The Creem webhook event was already processed.');

    expect($receivedEventId)->toBe('evt_fixture_license_created');
});
