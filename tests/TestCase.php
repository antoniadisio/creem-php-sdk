<?php

declare(strict_types=1);

namespace Creem\Tests;

use Creem\Tests\Support\InteractsWithFixtures;
use Creem\Tests\Support\InteractsWithMockRequests;
use Creem\Tests\Support\InteractsWithOpenApiSpec;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithFixtures;
    use InteractsWithMockRequests;
    use InteractsWithOpenApiSpec;
}
