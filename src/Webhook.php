<?php

declare(strict_types=1);

namespace Antoniadisio\Creem;

use Antoniadisio\Creem\Dto\Webhook\WebhookEvent;
use Antoniadisio\Creem\Exception\HydrationException;
use Antoniadisio\Creem\Exception\InvalidWebhookPayloadException;
use Antoniadisio\Creem\Exception\InvalidWebhookSignatureException;
use Antoniadisio\Creem\Internal\Webhook\PayloadDecoder;
use Antoniadisio\Creem\Internal\Webhook\SecureComparer;
use Antoniadisio\Creem\Internal\Webhook\Signature;

use function trim;

final class Webhook
{
    private function __construct() {}

    public static function verifySignatureForProfile(
        string $payload,
        string $signatureHeader,
        string $profileName,
        CredentialProfiles $profiles,
    ): void {
        self::verifySignature(
            $payload,
            $signatureHeader,
            $profiles->webhookSecret($profileName),
        );
    }

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

        $expectedSignature = Signature::compute($payload, $normalizedSecret);

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

    public static function constructEventForProfile(
        string $payload,
        string $signatureHeader,
        string $profileName,
        CredentialProfiles $profiles,
        ?callable $isReplay = null,
    ): WebhookEvent {
        return self::constructEvent(
            $payload,
            $signatureHeader,
            $profiles->webhookSecret($profileName),
            $isReplay,
        );
    }
}
