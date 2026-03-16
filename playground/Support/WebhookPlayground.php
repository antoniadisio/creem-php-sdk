<?php

declare(strict_types=1);

namespace Playground\Support;

use Antoniadisio\Creem\Webhook;
use JsonException;
use Throwable;

use function array_change_key_case;
use function array_is_list;
use function basename;
use function bin2hex;
use function count;
use function date;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function is_array;
use function is_scalar;
use function is_string;
use function json_decode;
use function random_bytes;
use function rtrim;
use function sort;
use function sprintf;
use function trim;

final class WebhookPlayground
{
    public static function captureDirectory(string $workspacePath): string
    {
        return self::workspacePathOverride(
            'CREEM_PLAYGROUND_WEBHOOK_CAPTURE_PATH',
            $workspacePath . '/captures/webhooks',
        );
    }

    public static function captureHealth(string $workspacePath): array
    {
        $paths = self::capturePaths($workspacePath);
        $activeProfile = null;
        $secretConfigured = false;
        $profileError = null;

        try {
            $values = self::playgroundValues($workspacePath);
            $activeProfile = Playground::activeProfileName($values);
            $secretConfigured = Playground::profileHasWebhookSecret($values, $activeProfile);
        } catch (Throwable $exception) {
            $profileError = $exception::class . ': ' . $exception->getMessage();
        }

        return [
            'ok' => true,
            'capture_count' => count($paths),
            'latest_capture' => $paths === [] ? null : basename($paths[array_key_last($paths)]),
            'active_profile' => $activeProfile,
            'secret_configured' => $secretConfigured,
            'profile_error' => $profileError,
        ];
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, string>
     */
    public static function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach (array_change_key_case($headers, CASE_LOWER) as $key => $value) {
            if (is_string($key) && is_scalar($value)) {
                $normalized[$key] = trim((string) $value);
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    public static function capture(string $workspacePath, string $path, array $headers, string $payload): array
    {
        $normalizedHeaders = self::normalizeHeaders($headers);
        $analysis = self::analyze($workspacePath, $path, $normalizedHeaders, $payload);
        $capture = [
            'captured_at' => date(DATE_ATOM),
            'path' => $path,
            'headers' => $normalizedHeaders,
            'analysis' => $analysis,
            'payload' => $analysis['payload'],
            'raw_payload' => $payload,
        ];

        $file = self::storeCapture($workspacePath, $capture);

        return [
            'ok' => true,
            'stored' => basename($file),
            'path' => $path,
            'profile' => $analysis['profile'],
            'verified' => $analysis['verified'],
            'event_type' => $analysis['event_type'],
            'mode_paths' => $analysis['mode_paths'],
            'verification_error' => $analysis['verification_error'],
            'parse_error' => $analysis['parse_error'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function inspect(
        string $workspacePath,
        int $limit = 10,
        bool $latestOnly = false,
        ?string $profileName = null,
    ): array {
        $paths = self::capturePaths($workspacePath);

        if ($latestOnly && $paths !== []) {
            $paths = [$paths[array_key_last($paths)]];
        } elseif ($limit > 0 && count($paths) > $limit) {
            $paths = array_slice($paths, -$limit);
        }

        $captures = [];

        foreach ($paths as $path) {
            $capture = self::loadCapture($path);

            if ($capture === null) {
                $captures[] = [
                    'file' => basename($path),
                    'error' => 'Capture file is not valid JSON.',
                ];

                continue;
            }

            $headers = is_array($capture['headers'] ?? null)
                ? self::normalizeHeaders($capture['headers'])
                : [];
            $rawPayload = is_string($capture['raw_payload'] ?? null) ? $capture['raw_payload'] : '';
            $analysis = self::analyze(
                $workspacePath,
                is_string($capture['path'] ?? null) ? $capture['path'] : '/',
                $headers,
                $rawPayload,
                $profileName,
            );

            $captures[] = [
                'file' => basename($path),
                'captured_at' => $capture['captured_at'] ?? null,
                'path' => $capture['path'] ?? null,
                'profile' => $analysis['profile'],
                'verified' => $analysis['verified'],
                'event_id' => $analysis['event_id'],
                'event_type' => $analysis['event_type'],
                'mode_paths' => $analysis['mode_paths'],
                'verification_error' => $analysis['verification_error'],
                'parse_error' => $analysis['parse_error'],
            ];
        }

        return $captures;
    }

    /**
     * @param  array<string, string>  $headers
     * @return array{
     *     profile: string|null,
     *     verified: bool,
     *     event_id: string|null,
     *     event_type: string|null,
     *     mode_paths: list<array{path: string, value: string}>,
     *     verification_error: string|null,
     *     parse_error: string|null,
     *     payload: array<string, mixed>|null
     * }
     */
    public static function analyze(
        string $workspacePath,
        string $path,
        array $headers,
        string $payload,
        ?string $profileName = null,
    ): array {
        $signature = $headers['creem-signature'] ?? '';
        $verified = false;
        $resolvedProfile = null;
        $eventId = null;
        $eventType = null;
        $verificationError = null;
        $parseError = null;

        $decoded = self::decodePayload($payload);
        $modePaths = is_array($decoded) ? self::modePaths($decoded) : [];
        $values = null;

        try {
            $values = self::playgroundValues($workspacePath, $profileName, $path);
            $resolvedProfile = Playground::activeProfileName($values);
        } catch (Throwable $exception) {
            $verificationError = $exception::class . ': ' . $exception->getMessage();
        }

        if (
            is_array($values)
            && is_string($resolvedProfile)
            && Playground::profileHasWebhookSecret($values, $resolvedProfile)
        ) {
            try {
                $event = Webhook::constructEventForProfile(
                    $payload,
                    $signature,
                    $resolvedProfile,
                    Playground::credentialProfiles($values, $resolvedProfile, true),
                );
                $verified = true;
                $eventId = $event->id();
                $eventType = $event->eventType();
            } catch (Throwable $exception) {
                $verificationError = $exception::class . ': ' . $exception->getMessage();
            }
        }

        if ($eventType === null) {
            try {
                $event = Webhook::parseEvent($payload);
                $eventId = $event->id();
                $eventType = $event->eventType();
            } catch (Throwable $exception) {
                $parseError = $exception::class . ': ' . $exception->getMessage();
            }
        }

        return [
            'profile' => $resolvedProfile,
            'verified' => $verified,
            'event_id' => $eventId,
            'event_type' => $eventType,
            'mode_paths' => $modePaths,
            'verification_error' => $verificationError,
            'parse_error' => $parseError,
            'payload' => $decoded,
        ];
    }

    /**
     * @return list<string>
     */
    private static function capturePaths(string $workspacePath): array
    {
        $paths = glob(self::captureDirectory($workspacePath) . '/*.json') ?: [];
        sort($paths);

        return $paths;
    }

    /**
     * @param  array<string, mixed>  $capture
     */
    private static function storeCapture(string $workspacePath, array $capture): string
    {
        $directory = self::captureDirectory($workspacePath);

        if (! file_exists($directory) && ! mkdir($directory, 0777, true) && ! file_exists($directory)) {
            throw new PlaygroundException(sprintf('Unable to create webhook capture directory [%s].', $directory));
        }

        $file = sprintf(
            '%s/%s-%s.json',
            rtrim($directory, '/'),
            date('Ymd-His'),
            bin2hex(random_bytes(4)),
        );

        if (file_put_contents($file, self::encode($capture) . "\n") === false) {
            throw new PlaygroundException(sprintf('Unable to write webhook capture file [%s].', $file));
        }

        return $file;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function loadCapture(string $path): ?array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            return null;
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function decodePayload(string $payload): ?array
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            return null;
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private static function playgroundValues(
        string $workspacePath,
        ?string $profileName = null,
        ?string $path = null,
    ): array {
        $workspace = Playground::workspace($workspacePath);

        return Playground::buildProfileValues(
            Playground::loadState($workspace['state_path'], $workspace['state_example_path']),
            $profileName,
            $path,
            true,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{path: string, value: string}>
     */
    private static function modePaths(array $payload): array
    {
        $paths = [];

        self::collectModePaths($payload, 'payload', $paths);

        return $paths;
    }

    /**
     * @param  array<string, mixed>|list<mixed>  $node
     * @param  list<array{path: string, value: string}>  $paths
     */
    private static function collectModePaths(array $node, string $path, array &$paths): void
    {
        if (array_key_exists('mode', $node) && is_scalar($node['mode'])) {
            $paths[] = [
                'path' => $path . '.mode',
                'value' => (string) $node['mode'],
            ];
        }

        foreach ($node as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $childPath = is_int($key) ? $path . '[' . $key . ']' : $path . '.' . $key;
            self::collectModePaths($value, $childPath, $paths);
        }
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private static function encode(array $value): string
    {
        try {
            return (string) json_encode(
                Playground::normalize($value),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new PlaygroundException('Unable to encode webhook capture JSON.', previous: $exception);
        }
    }

    private static function workspacePathOverride(string $envName, string $default): string
    {
        $value = getenv($envName);

        if (! is_string($value)) {
            return $default;
        }

        $value = trim($value);

        return $value === '' ? $default : $value;
    }
}
