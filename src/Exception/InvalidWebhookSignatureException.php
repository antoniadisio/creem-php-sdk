<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Exception;

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

    public static function invalidSignature(): self
    {
        return new self('The Creem webhook signature is invalid.');
    }

    public static function replayedEvent(): self
    {
        return new self('The Creem webhook event was already processed.');
    }
}
