<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests;

use Antoniadisio\Creem\Tests\Support\Contract\InteractsWithContractSupport;
use Antoniadisio\Creem\Tests\Support\InteractsWithFixtures;
use Antoniadisio\Creem\Tests\Support\InteractsWithMockRequests;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithContractSupport;
    use InteractsWithFixtures;
    use InteractsWithMockRequests;
}
