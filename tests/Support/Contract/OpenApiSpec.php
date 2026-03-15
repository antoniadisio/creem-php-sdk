<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Support\Contract;

use JsonException;
use PHPUnit\Framework\Assert;

use function array_is_list;
use function ctype_digit;
use function dirname;
use function explode;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function ksort;
use function sprintf;
use function strtoupper;

final readonly class OpenApiSpec
{
    /**
     * @param  array<string, mixed>  $spec
     */
    private function __construct(private array $spec) {}

    /**
     * @throws JsonException
     */
    public static function fromFixture(): self
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/Fixtures/OpenApi/creem-openapi.json');

        Assert::assertNotFalse($contents, 'OpenAPI spec could not be read.');

        $spec = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        Assert::assertIsArray($spec, 'OpenAPI spec must decode to an object payload.');
        Assert::assertFalse(array_is_list($spec), 'OpenAPI spec must decode to an object payload.');

        /** @var array<string, mixed> $spec */
        return new self($spec);
    }

    /**
     * @return list<int|string>
     */
    public function enumValuesAtPath(string $path): array
    {
        $node = $this->nodeAtPath($path);

        Assert::assertIsArray($node, sprintf('Spec path %s must resolve to an enum schema.', $path));
        Assert::assertArrayHasKey('enum', $node, sprintf('Spec path %s must expose an enum.', $path));
        Assert::assertIsArray($node['enum'], sprintf('Spec path %s must expose enum values.', $path));
        Assert::assertTrue(array_is_list($node['enum']), sprintf('Spec path %s must expose enum values as a list.', $path));

        /** @var list<int|string> $enum */
        $enum = $node['enum'];

        return $enum;
    }

    /**
     * @return array<string, mixed>
     */
    public function objectAtPath(string $path): array
    {
        $node = $this->nodeAtPath($path);

        Assert::assertIsArray($node, sprintf('Spec path %s must resolve to an object schema.', $path));
        Assert::assertFalse(array_is_list($node), sprintf('Spec path %s must resolve to an object schema.', $path));

        /** @var array<string, mixed> $node */
        return $node;
    }

    /**
     * @return array<string, array{method: string, path: string}>
     */
    public function operations(): array
    {
        $paths = $this->objectValue($this->spec, 'paths', 'OpenAPI spec');
        $operations = [];

        foreach ($paths as $path => $methods) {
            if (! is_array($methods) || array_is_list($methods)) {
                continue;
            }

            /** @var array<string, mixed> $methods */
            foreach ($methods as $method => $operation) {
                if (! is_array($operation) || array_is_list($operation)) {
                    continue;
                }

                /** @var array<string, mixed> $operation */
                $operationId = $operation['operationId'] ?? null;

                if (! is_string($operationId)) {
                    continue;
                }

                Assert::assertArrayNotHasKey($operationId, $operations, 'OpenAPI operation IDs must be unique.');

                $operations[$operationId] = [
                    'method' => strtoupper($method),
                    'path' => $path,
                ];
            }
        }

        ksort($operations);

        return $operations;
    }

    private function nodeAtPath(string $path): mixed
    {
        $node = $this->spec;

        foreach (explode('.', $path) as $segment) {
            Assert::assertIsArray($node, sprintf('Spec path %s must resolve at every segment.', $path));

            $key = ctype_digit($segment) ? (int) $segment : $segment;

            Assert::assertArrayHasKey($key, $node, sprintf('Spec path %s is missing segment %s.', $path, $segment));

            $node = $node[$key];
        }

        return $node;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function objectValue(array $payload, string $key, string $context): array
    {
        Assert::assertArrayHasKey($key, $payload, sprintf('%s must contain key %s.', $context, $key));

        $value = $payload[$key];

        Assert::assertIsArray($value, sprintf('%s.%s must be an object.', $context, $key));
        Assert::assertFalse(array_is_list($value), sprintf('%s.%s must be an object.', $context, $key));

        /** @var array<string, mixed> $value */
        return $value;
    }
}
