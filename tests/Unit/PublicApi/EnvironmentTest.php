<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Enum\Environment;

test('environment resolves base urls', function (): void {
    expect(Environment::Production->baseUrl())->toBe('https://api.creem.io')
        ->and(Environment::Test->baseUrl())->toBe('https://test-api.creem.io');
});
