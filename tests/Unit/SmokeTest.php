<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function test_phase_two_workspace_artifacts_exist(): void
    {
        $repositoryRoot = dirname(__DIR__, 2);

        self::assertFileExists($repositoryRoot.'/fern/fern.config.json');
        self::assertFileExists($repositoryRoot.'/fern/generators.yml');
        self::assertFileExists($repositoryRoot.'/fern/definition/openapi/creem-openapi.json');
        self::assertFileExists($repositoryRoot.'/fern/.definition/api.yml');
        self::assertFileExists($repositoryRoot.'/docs/spec-audit.md');
    }
}
