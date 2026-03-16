<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Exception\InvalidWebhookPayloadException;
use Antoniadisio\Creem\Internal\Webhook\PayloadDecoder;
use InvalidArgumentException;

test('webhook payload decoder rejects non-object json payloads', function (): void {
    expect(static fn(): array => PayloadDecoder::decode('["not-an-object"]'))
        ->toThrow(InvalidWebhookPayloadException::class, 'The Creem webhook payload must decode to a JSON object.');
});

test('webhook payload decoder rejects invalid depth arguments', function (): void {
    expect(static fn(): array => PayloadDecoder::decode('{}', 0))
        ->toThrow(InvalidArgumentException::class, 'The webhook payload JSON depth must be at least 1.');
});
