<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http;

use Antoniadisio\Creem\Exception\TransportException;
use JsonException;
use Saloon\Http\Response;

use function array_is_list;
use function is_array;
use function json_decode;
use function trim;

final class ResponseDecoder
{
    /**
     * @return array<string, mixed>
     */
    public static function decode(Response $response): array
    {
        $body = trim($response->body());

        if ($body === '') {
            return [];
        }

        try {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new TransportException('The Creem API returned an invalid JSON response.', null, [], $exception);
        }

        if (! is_array($payload) || array_is_list($payload)) {
            throw new TransportException('The Creem API returned an unexpected JSON payload shape.');
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }
}
