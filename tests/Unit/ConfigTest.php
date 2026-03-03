<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Config;
use Creem\Environment;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function test_config_applies_overrides_and_normalizes_inputs(): void
    {
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
    }

    public function test_config_rejects_invalid_values(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Config('');
    }
}
