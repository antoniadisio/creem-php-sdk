<?php

declare(strict_types=1);

namespace Creem\Exception;

final class InvalidWebhookSignatureException extends WebhookException
{
    public static function missingSecret(): self
    {
        return new self('The Creem webhook secret is missing or blank.');
    }

    public static function missingSignature(): self
    {
        return new self('The Creem webhook signature header is missing or blank.');
    }

    public static function missingTimestamp(): self
    {
        return new self('The Creem webhook signature timestamp is missing.');
    }

    public static function invalidTimestamp(): self
    {
        return new self('The Creem webhook signature timestamp is invalid.');
    }

    public static function expiredTimestamp(): self
    {
        return new self('The Creem webhook signature timestamp is outside the allowed tolerance.');
    }

    public static function invalidSignature(): self
    {
        return new self('The Creem webhook signature is invalid.');
    }

    public static function replayedEvent(): self
    {
        return new self('The Creem webhook event was already processed.');
    }
}
