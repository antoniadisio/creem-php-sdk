<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Support;

use JsonException;
use RuntimeException;

use function array_is_list;
use function array_merge;
use function bin2hex;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function getenv;
use function is_array;
use function is_resource;
use function json_decode;
use function json_encode;
use function mkdir;
use function proc_close;
use function proc_open;
use function random_bytes;
use function scandir;
use function stream_get_contents;
use function sys_get_temp_dir;
use function unlink;

use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

final class PlaygroundTestSupport
{
    public static function repoRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function playgroundWorkspacePath(): string
    {
        return self::repoRoot() . '/playground';
    }

    public static function stateExamplePath(): string
    {
        return self::playgroundWorkspacePath() . '/state.example.json';
    }

    public static function tempDir(string $prefix = 'creem-playground-'): string
    {
        $path = sys_get_temp_dir() . '/' . $prefix . bin2hex(random_bytes(8));

        if (! mkdir($path, 0777, true) && ! file_exists($path)) {
            throw new RuntimeException('Unable to create playground temp directory.');
        }

        return $path;
    }

    public static function removeDirectory(string $path): void
    {
        if (! file_exists($path)) {
            return;
        }

        $entries = scandir($path);

        if (! is_array($entries)) {
            throw new RuntimeException('Unable to scan playground temp directory.');
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $entryPath = $path . '/' . $entry;

            if (is_dir($entryPath)) {
                self::removeDirectory($entryPath);

                continue;
            }

            unlink($entryPath);
        }

        rmdir($path);
    }

    /**
     * @param  array<string, string>  $env
     */
    public static function withEnvironment(array $env, callable $callback): mixed
    {
        $previous = [];

        foreach ($env as $key => $value) {
            $existing = getenv($key);
            $previous[$key] = $existing === false ? null : $existing;
            putenv($key . '=' . $value);
        }

        try {
            return $callback();
        } finally {
            foreach ($previous as $key => $value) {
                if ($value === null) {
                    putenv($key);

                    continue;
                }

                putenv($key . '=' . $value);
            }
        }
    }

    public static function writeFile(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException("Unable to write [{$path}].");
        }
    }

    public static function readFile(string $path): string
    {
        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException("Unable to read [{$path}].");
        }

        return $contents;
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public static function writeJsonFile(string $path, array $value): void
    {
        try {
            self::writeFile($path, json_encode($value, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode playground JSON.', $exception->getCode(), previous: $exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function decodeJsonObject(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to decode JSON output.', $exception->getCode(), previous: $exception);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new RuntimeException('Expected decoded JSON to be an object.');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @param  list<string>  $command
     * @param  array<string, string>  $env
     * @return array{exitCode: int, stdout: string, stderr: string}
     */
    public static function runCommand(array $command, ?string $stdin = null, array $env = [], ?string $cwd = null): array
    {
        /** @var array<string, string> $environment */
        $environment = getenv();
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $cwd ?? self::repoRoot(),
            array_merge($environment, $env),
        );

        if (! is_resource($process)) {
            throw new RuntimeException('Unable to start process.');
        }

        if ($stdin !== null) {
            self::writeToPipe($pipes[0], $stdin);
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [
            'exitCode' => proc_close($process),
            'stdout' => is_string($stdout) ? $stdout : '',
            'stderr' => is_string($stderr) ? $stderr : '',
        ];
    }

    /**
     * @param  list<string>  $arguments
     * @param  array<string, string>  $env
     * @return array{exitCode: int, stdout: string, stderr: string}
     */
    public static function runPhpScript(string $scriptPath, array $arguments = [], ?string $stdin = null, array $env = []): array
    {
        return self::runCommand(
            array_merge([PHP_BINARY, $scriptPath], $arguments),
            $stdin,
            $env,
        );
    }

    /**
     * @param  resource  $pipe
     */
    private static function writeToPipe(mixed $pipe, string $contents): void
    {
        if (! is_resource($pipe)) {
            throw new RuntimeException('Expected process stdin pipe resource.');
        }

        $written = fwrite($pipe, $contents);

        if ($written === false) {
            throw new RuntimeException('Unable to write process stdin.');
        }
    }
}
