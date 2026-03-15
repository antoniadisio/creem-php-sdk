<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Exception;

use Throwable;

use function sprintf;

final class InvalidWebhookPayloadException extends WebhookException
{
    public static function payloadTooLarge(int $maxBytes): self
    {
        return new self(sprintf('The Creem webhook payload exceeds the %d byte limit.', $maxBytes));
    }

    public static function invalidJson(Throwable $previous): self
    {
        return new self('The Creem webhook payload is not valid JSON.', null, [], $previous);
    }

    public static function unexpectedPayloadShape(): self
    {
        return new self('The Creem webhook payload must decode to a JSON object.');
    }

    public static function invalidEvent(Throwable $previous): self
    {
        return new self('The Creem webhook payload is not a valid event object.', null, [], $previous);
    }
}
