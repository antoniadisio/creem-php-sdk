<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Tests\TestCase;
use ReflectionMethod;

use function array_map;
use function array_unique;
use function array_values;
use function basename;
use function glob;
use function sort;
use function sprintf;

test('openapi spec operations match the explicit sdk coverage manifest', function (): void {
    /** @var TestCase $this */
    $this->assertSame($this->coverageManifest()->specOperations(), $this->openApiSpec()->operations());
});

test('every spec operation maps to public sdk methods and a resource-owned integration test file', function (): void {
    /** @var TestCase $this */
    $expectedIntegrationFiles = [];

    foreach ($this->coverageManifest()->entries() as $operationId => $coverage) {
        foreach ($coverage['sdk_methods'] as $sdkMethod) {
            $this->assertTrue(method_exists($coverage['resource'], $sdkMethod), sprintf('Operation %s must map to an existing SDK method.', $operationId));

            $resourceMethod = new ReflectionMethod($coverage['resource'], $sdkMethod);

            $this->assertTrue($resourceMethod->isPublic(), sprintf('Operation %s must map to a public SDK method.', $operationId));
        }

        $expectedIntegrationFiles[] = basename($this->coverageManifest()->integrationTestFileForResource($coverage['resource']));
        $this->assertFileExists(
            $this->coverageManifest()->integrationTestFileForResource($coverage['resource']),
            sprintf('Operation %s must be owned by a resource integration test file.', $operationId),
        );
    }

    $expectedIntegrationFiles = array_values(array_unique($expectedIntegrationFiles));
    $actualIntegrationFiles = array_map(
        basename(...),
        glob(dirname(__DIR__, 2).'/Integration/*ResourceTest.php') ?: [],
    );

    sort($expectedIntegrationFiles);
    sort($actualIntegrationFiles);

    $this->assertSame($expectedIntegrationFiles, $actualIntegrationFiles);
});
