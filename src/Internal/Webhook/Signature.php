<?php

declare(strict_types=1);

namespace Creem\Internal\Webhook;

use Creem\Exception\InvalidWebhookSignatureException;

use function explode;
use function hash_hmac;
use function preg_match;
use function str_contains;
use function time;
use function trim;

final class Signature
{
    public const int DEFAULT_TOLERANCE_SECONDS = 300;

    public static function compute(string $payload, #[\SensitiveParameter]
        string $secret, ?int $timestamp = null): string
    {
        $normalizedSecret = trim($secret);

        if ($normalizedSecret === '') {
            throw InvalidWebhookSignatureException::missingSecret();
        }

        $signedPayload = $timestamp === null ? $payload : $timestamp.'.'.$payload;

        return hash_hmac('sha256', $signedPayload, $normalizedSecret);
    }

    /**
     * @return array{timestamp: int, signature: string}
     */
    public static function parseHeader(string $signatureHeader): array
    {
        $timestamp = null;
        $signature = null;
        $hasTimestampField = false;

        foreach (explode(',', $signatureHeader) as $segment) {
            $segment = trim($segment);

            if ($segment === '') {
                continue;
            }

            if (! str_contains($segment, '=')) {
                $signature ??= $segment;

                continue;
            }

            $parts = explode('=', $segment, 2);
            $key = trim($parts[0]);
            $value = trim($parts[1] ?? '');

            if ($key === 't' || $key === 'timestamp') {
                $hasTimestampField = true;

                if (preg_match('/^-?\d+$/', $value) === 1) {
                    $timestamp = (int) $value;
                }

                continue;
            }

            if (($key === 'v1' || $key === 'signature') && $value !== '') {
                $signature = $value;
            }
        }

        if ($signature === null) {
            throw InvalidWebhookSignatureException::missingSignature();
        }

        if (! $hasTimestampField) {
            throw InvalidWebhookSignatureException::missingTimestamp();
        }

        if ($timestamp === null) {
            throw InvalidWebhookSignatureException::invalidTimestamp();
        }

        return [
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];
    }

    public static function validateTimestamp(int $timestamp, int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS): void
    {
        $now = time();

        if ($timestamp < ($now - $toleranceSeconds) || $timestamp > ($now + $toleranceSeconds)) {
            throw InvalidWebhookSignatureException::expiredTimestamp();
        }
    }
}
