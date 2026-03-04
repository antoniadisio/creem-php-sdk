<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Environment;

test('environment resolves base urls', function (): void {
    $this->assertSame('https://api.creem.io', Environment::Production->baseUrl());
    $this->assertSame('https://test-api.creem.io', Environment::Test->baseUrl());
});
