<?php

declare(strict_types=1);

use Antoniadisio\Creem\Tests\IntegrationTestCase;
use Antoniadisio\Creem\Tests\SmokeTestCase;
use Antoniadisio\Creem\Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit');
pest()->group('repo')->in('Unit/Contract', 'Unit/Playground');
pest()->extend(IntegrationTestCase::class)->in('Integration');
pest()->extend(SmokeTestCase::class)->group('smoke', 'network')->in('Smoke');
