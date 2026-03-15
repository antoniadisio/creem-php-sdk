<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Tests\TestCase;

test('response fixtures use sanitized placeholder values', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;

    foreach ($testCase->responseFixtureCatalog()->names() as $fixture) {
        $testCase->responseFixturePolicy()->assertSanitizedFixture($testCase, $fixture, $testCase->fixture($fixture));
    }
});
