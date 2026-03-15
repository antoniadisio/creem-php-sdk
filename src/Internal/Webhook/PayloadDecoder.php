<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Webhook;

use Antoniadisio\Creem\Exception\InvalidWebhookPayloadException;
use InvalidArgumentException;
use JsonException;

use function array_is_list;
use function is_array;
use function json_decode;
use function strlen;

final class PayloadDecoder
{
    private const int DEFAULT_MAX_DEPTH = 128;

    private const int DEFAULT_MAX_BYTES = 1_048_576;

    /**
     * @return array<string, mixed>
     */
    public static function decode(
        string $payload,
        int $depth = self::DEFAULT_MAX_DEPTH,
        int $maxBytes = self::DEFAULT_MAX_BYTES,
    ): array {
        if ($depth < 1) {
            throw new InvalidArgumentException('The webhook payload JSON depth must be at least 1.');
        }

        if (strlen($payload) > $maxBytes) {
            throw InvalidWebhookPayloadException::payloadTooLarge($maxBytes);
        }

        try {
            $decoded = json_decode($payload, true, $depth, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw InvalidWebhookPayloadException::invalidJson($exception);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw InvalidWebhookPayloadException::unexpectedPayloadShape();
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
