<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Support;

use const JSON_THROW_ON_ERROR;

use Antoniadisio\Creem\Internal\Webhook\Signature;
use JsonException;

use function array_replace_recursive;
use function json_encode;

final class WebhookTestSupport
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function eventPayloadArray(array $overrides = []): array
    {
        /** @var array<string, mixed> $payload */
        $payload = array_replace_recursive([
            'id' => 'evt_fixture_subscription_active',
            'eventType' => 'subscription.active',
            'created_at' => '2026-03-07T06:49:26+00:00',
            'object' => [
                'id' => 'sub_fixture_primary',
                'object' => 'subscription',
                'status' => 'active',
            ],
        ], $overrides);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $overrides
     *
     * @throws JsonException
     */
    public static function eventPayload(array $overrides = []): string
    {
        return self::encodePayload(self::eventPayloadArray($overrides));
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws JsonException
     */
    public static function encodePayload(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    public static function signatureHeader(
        string $payload,
        #[\SensitiveParameter]
        string $secret = 'whsec_test_secret',
        ?string $signature = null,
    ): string {
        return $signature ?? Signature::compute($payload, $secret);
    }
}
