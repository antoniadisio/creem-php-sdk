<?php

declare(strict_types=1);

namespace Creem;

use Creem\Dto\Webhook\WebhookEvent;
use Creem\Exception\HydrationException;
use Creem\Exception\InvalidWebhookPayloadException;
use Creem\Exception\InvalidWebhookSignatureException;
use Creem\Internal\Webhook\PayloadDecoder;
use Creem\Internal\Webhook\SecureComparer;
use Creem\Internal\Webhook\Signature;

use function trim;

final class Webhook
{
    private function __construct() {}

    public static function verifySignature(string $payload, string $signatureHeader, string $secret): void
    {
        $normalizedSignature = trim($signatureHeader);

        if ($normalizedSignature === '') {
            throw InvalidWebhookSignatureException::missingSignature();
        }

        $expectedSignature = Signature::compute($payload, $secret);

        if (! SecureComparer::equals($expectedSignature, $normalizedSignature)) {
            throw InvalidWebhookSignatureException::invalidSignature();
        }
    }

    public static function parseEvent(string $payload): WebhookEvent
    {
        try {
            return WebhookEvent::fromPayload(PayloadDecoder::decode($payload));
        } catch (HydrationException $exception) {
            throw InvalidWebhookPayloadException::invalidEvent($exception);
        }
    }

    public static function constructEvent(string $payload, string $signatureHeader, string $secret): WebhookEvent
    {
        self::verifySignature($payload, $signatureHeader, $secret);

        return self::parseEvent($payload);
    }
}
