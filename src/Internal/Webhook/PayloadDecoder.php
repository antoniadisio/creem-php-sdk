<?php

declare(strict_types=1);

namespace Creem\Internal\Webhook;

use Creem\Exception\InvalidWebhookPayloadException;
use JsonException;

use function array_is_list;
use function is_array;
use function json_decode;

final class PayloadDecoder
{
    /**
     * @return array<string, mixed>
     */
    public static function decode(string $payload): array
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
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
