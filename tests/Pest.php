<?php

declare(strict_types=1);

use Creem\Tests\TestCase;

uses(TestCase::class)->in('Unit');

/**
 * @param-closure-this TestCase  $closure
 */
function creem_test(string $description, \Closure $closure): void
{
    test($description, $closure);
}
