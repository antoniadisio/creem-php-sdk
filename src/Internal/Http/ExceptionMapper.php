<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http;

use Antoniadisio\Creem\Exception\AuthenticationException;
use Antoniadisio\Creem\Exception\CreemException;
use Antoniadisio\Creem\Exception\NotFoundException;
use Antoniadisio\Creem\Exception\RateLimitException;
use Antoniadisio\Creem\Exception\ServerException;
use Antoniadisio\Creem\Exception\ValidationException;
use Saloon\Http\Response;

use function array_is_list;
use function array_key_exists;
use function array_slice;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function max;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strtotime;
use function substr;
use function time;
use function trim;

final class ExceptionMapper
{
    private const string REDACTED_PLACEHOLDER = '[redacted]';

    private const string TRUNCATED_PLACEHOLDER = '[truncated]';

    private const int MAX_ERROR_DEPTH = 5;

    private const int MAX_CONTEXT_DEPTH = 4;

    private const int MAX_CONTEXT_ITEMS = 10;

    private const int MAX_STRING_LENGTH = 500;

    private const string SENSITIVE_TOKEN_PATTERN = '/(?<![A-Za-z0-9])(?:sk|creem|whsec)_[A-Za-z0-9][A-Za-z0-9._-]*/';

    /**
     * @var list<string>
     */
    private const array SAFE_CONTEXT_KEYS = ['message', 'error', 'detail', 'title', 'code', 'type', 'request_id'];

    /**
     * @var list<string>
     */
    private const array SAFE_ERROR_SCALAR_KEYS = ['message', 'detail', 'error', 'title', 'code', 'type', 'field', 'param', 'pointer'];

    public static function map(Response $response): CreemException
    {
        $statusCode = $response->status();
        $context = self::buildContext($response);
        $message = self::resolveMessage($statusCode, $context);
        $retryAfterSeconds = self::parseRetryAfterHeader($response);

        if ($retryAfterSeconds !== null) {
            $context['retry_after_seconds'] = $retryAfterSeconds;
        }

        if (self::isValidationFailure($statusCode, $context)) {
            return new ValidationException($message, $statusCode, $context);
        }

        return match (true) {
            $statusCode === 401 || $statusCode === 403 => new AuthenticationException($message, $statusCode, $context),
            $statusCode === 404 => new NotFoundException($message, $statusCode, $context),
            $statusCode === 429 => new RateLimitException($message, $statusCode, $context, null, $retryAfterSeconds),
            $statusCode >= 500 => new ServerException($message, $statusCode, $context),
            default => new CreemException($message, $statusCode, $context),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildContext(Response $response): array
    {
        $body = trim($response->body());

        if ($body === '') {
            return [];
        }

        if (self::shouldDecodeJson($response, $body)) {
            return self::sanitizeContext(ResponseDecoder::decode($response));
        }

        return ['body' => self::truncate($body)];
    }

    private static function shouldDecodeJson(Response $response, string $body): bool
    {
        return $response->isJson() || str_starts_with($body, '{') || str_starts_with($body, '[');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function isValidationFailure(int $statusCode, array $context): bool
    {
        return $statusCode === 422
            || ($statusCode >= 400 && $statusCode < 500 && isset($context['errors']) && is_array($context['errors']));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function resolveMessage(int $statusCode, array $context): string
    {
        $message = self::extractMessage($context);

        if ($message !== null) {
            return $message;
        }

        return sprintf('The Creem API request failed with status %d.', $statusCode);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function extractMessage(array $context): ?string
    {
        foreach (['message', 'error', 'detail', 'title', 'body'] as $key) {
            $value = $context[$key] ?? null;
            $message = self::meaningfulMessageOrNull($value);

            if ($message !== null) {
                return $message;
            }
        }

        $errors = $context['errors'] ?? null;
        $message = self::meaningfulMessageOrNull($errors);

        if ($message !== null) {
            return $message;
        }

        if (! is_array($errors)) {
            return null;
        }

        return self::extractMessageFromErrors($errors);
    }

    /**
     * @param  array<array-key, mixed>  $errors
     */
    private static function extractMessageFromErrors(array $errors, int $depth = 0): ?string
    {
        if ($depth >= self::MAX_ERROR_DEPTH) {
            return null;
        }

        foreach ($errors as $error) {
            $message = self::meaningfulMessageOrNull($error);

            if ($message !== null) {
                return $message;
            }

            if (! is_array($error)) {
                continue;
            }

            foreach (['message', 'detail', 'error', 'title'] as $key) {
                $value = $error[$key] ?? null;
                $message = self::meaningfulMessageOrNull($value);

                if ($message !== null) {
                    return $message;
                }
            }

            $nestedMessage = self::extractMessageFromErrors($error, $depth + 1);

            if ($nestedMessage !== null) {
                return $nestedMessage;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private static function sanitizeContext(array $context): array
    {
        $sanitized = [];

        foreach (self::SAFE_CONTEXT_KEYS as $key) {
            $value = $context[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                $sanitized[$key] = self::truncate($value);

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeValue($value, 0, true);

                continue;
            }

            if (is_int($value) || is_float($value) || is_bool($value)) {
                $sanitized[$key] = $value;
            }
        }

        if (array_key_exists('errors', $context)) {
            $sanitized['errors'] = self::sanitizeValue($context['errors'], 0, true);
        }

        return $sanitized;
    }

    private static function sanitizeValue(mixed $value, int $depth, bool $preserveFreeStrings = false): mixed
    {
        if ($depth >= self::MAX_CONTEXT_DEPTH) {
            return self::TRUNCATED_PLACEHOLDER;
        }

        if (is_string($value)) {
            return $preserveFreeStrings ? self::truncate($value) : self::REDACTED_PLACEHOLDER;
        }

        if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
            return $value;
        }

        if (! is_array($value)) {
            return self::REDACTED_PLACEHOLDER;
        }

        $sanitized = [];

        foreach (array_slice($value, 0, self::MAX_CONTEXT_ITEMS, true) as $key => $item) {
            if (is_array($item)) {
                $sanitized[$key] = self::sanitizeValue($item, $depth + 1, $preserveFreeStrings);

                continue;
            }

            if (array_is_list($value)) {
                $sanitized[$key] = self::sanitizeValue($item, $depth + 1, true);

                continue;
            }

            if (is_string($key) && self::isSafeErrorScalarKey($key)) {
                $sanitized[$key] = self::sanitizeValue($item, $depth + 1, true);

                continue;
            }

            $sanitized[$key] = self::REDACTED_PLACEHOLDER;
        }

        return $sanitized;
    }

    private static function isSafeErrorScalarKey(string $key): bool
    {
        return in_array($key, self::SAFE_ERROR_SCALAR_KEYS, true);
    }

    private static function meaningfulMessageOrNull(mixed $value): ?string
    {
        if (is_array($value)) {
            return self::firstMeaningfulMessageFromArray($value, 0);
        }

        if (! is_string($value)) {
            return null;
        }

        $value = self::truncate($value);

        if (in_array($value, ['', self::REDACTED_PLACEHOLDER, self::TRUNCATED_PLACEHOLDER], true)) {
            return null;
        }

        return $value;
    }

    /**
     * @param  array<array-key, mixed>  $value
     */
    private static function firstMeaningfulMessageFromArray(array $value, int $depth): ?string
    {
        if ($depth >= self::MAX_ERROR_DEPTH) {
            return null;
        }

        foreach (array_slice($value, 0, self::MAX_CONTEXT_ITEMS, true) as $key => $item) {
            if (! array_is_list($value) && is_string($key) && ! self::isSafeErrorScalarKey($key)) {
                continue;
            }

            if (is_array($item)) {
                $nestedMessage = self::firstMeaningfulMessageFromArray($item, $depth + 1);

                if ($nestedMessage !== null) {
                    return $nestedMessage;
                }

                continue;
            }

            $message = self::meaningfulMessageOrNull($item);

            if ($message !== null) {
                return $message;
            }
        }

        return null;
    }

    private static function truncate(string $value): string
    {
        $value = self::redactSensitiveTokens(trim($value));

        if (strlen($value) <= self::MAX_STRING_LENGTH) {
            return $value;
        }

        return substr($value, 0, self::MAX_STRING_LENGTH).'...';
    }

    private static function redactSensitiveTokens(string $value): string
    {
        return preg_replace(self::SENSITIVE_TOKEN_PATTERN, self::REDACTED_PLACEHOLDER, $value) ?? $value;
    }

    private static function parseRetryAfterHeader(Response $response): ?int
    {
        $retryAfter = $response->header('Retry-After');

        if (is_array($retryAfter)) {
            $retryAfter = $retryAfter[0] ?? null;
        }

        if (! is_string($retryAfter) || trim($retryAfter) === '') {
            return null;
        }

        $retryAfter = trim($retryAfter);

        if (preg_match('/^\d+$/', $retryAfter)) {
            return (int) $retryAfter;
        }

        $retryAt = strtotime($retryAfter);

        if ($retryAt === false) {
            return null;
        }

        return max(0, $retryAt - time());
    }
}
