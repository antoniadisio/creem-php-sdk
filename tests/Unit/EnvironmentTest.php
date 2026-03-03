<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Environment;
use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    public function test_environment_resolves_base_urls(): void
    {
        $this->assertSame('https://api.creem.io', Environment::Production->baseUrl());
        $this->assertSame('https://test-api.creem.io', Environment::Test->baseUrl());
    }
}
