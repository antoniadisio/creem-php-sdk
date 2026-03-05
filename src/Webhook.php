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

    public static function verifySignature(string $payload, string $signatureHeader, #[\SensitiveParameter]
        string $secret): void
    {
        $normalizedSignature = trim($signatureHeader);
        $normalizedSecret = trim($secret);

        if ($normalizedSignature === '') {
            throw InvalidWebhookSignatureException::missingSignature();
        }

        if ($normalizedSecret === '') {
            throw InvalidWebhookSignatureException::missingSecret();
        }

        $verifiedHeader = Signature::parseHeader($normalizedSignature);

        Signature::validateTimestamp($verifiedHeader['timestamp']);

        $expectedSignature = Signature::compute($payload, $normalizedSecret, $verifiedHeader['timestamp']);

        if (! SecureComparer::equals($expectedSignature, $verifiedHeader['signature'])) {
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

    public static function constructEvent(string $payload, string $signatureHeader, #[\SensitiveParameter]
        string $secret, ?callable $isReplay = null): WebhookEvent
    {
        self::verifySignature($payload, $signatureHeader, $secret);

        $event = self::parseEvent($payload);

        if ($isReplay !== null && $isReplay($event) === true) {
            throw InvalidWebhookSignatureException::replayedEvent();
        }

        return $event;
    }
}
