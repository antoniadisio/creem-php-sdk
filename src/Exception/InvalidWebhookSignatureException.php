<?php

declare(strict_types=1);

namespace Creem\Exception;

final class InvalidWebhookSignatureException extends WebhookException
{
    public static function missingSignature(): self
    {
        return new self('The Creem webhook signature header is missing or blank.');
    }

    public static function invalidSignature(): self
    {
        return new self('The Creem webhook signature is invalid.');
    }
}
