<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Config;
use Creem\Environment;
use InvalidArgumentException;

test('config applies overrides and normalizes inputs', function (): void {
    $config = new Config(
        '  sk_test_123  ',
        Environment::Test,
        'https://example.test/',
        15,
        '  integration-suite  ',
    );

    $this->assertSame('sk_test_123', $config->apiKey());
    $this->assertSame(Environment::Test, $config->environment());
    $this->assertSame('https://example.test', $config->baseUrl());
    $this->assertSame('https://example.test', $config->resolveBaseUrl());
    $this->assertEqualsWithDelta(15.0, $config->timeout(), PHP_FLOAT_EPSILON);
    $this->assertSame('integration-suite', $config->userAgentSuffix());
    $this->assertStringStartsWith('creem-php-sdk/', $config->userAgent());
    $this->assertStringContainsString('php/'.PHP_VERSION, $config->userAgent());
    $this->assertStringEndsWith('integration-suite', $config->userAgent());
});

test('config rejects invalid values', function (): void {
    expect(static function (): void {
        new Config('');
    })->toThrow(InvalidArgumentException::class);
});
