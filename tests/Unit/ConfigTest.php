<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Config;
use Creem\Enum\Environment;
use InvalidArgumentException;
use LogicException;

test('config applies overrides and normalizes inputs', function (): void {
    $config = new Config(
        '  sk_test_123  ',
        Environment::Test,
        'https://test-api.creem.io/',
        15,
        '  integration-suite  ',
    );

    expect($config->apiKey())->toBe('sk_test_123')
        ->and($config->environment())->toBe(Environment::Test)
        ->and($config->baseUrl())->toBe('https://test-api.creem.io')
        ->and($config->resolveBaseUrl())->toBe('https://test-api.creem.io')
        ->and($config->timeout())->toBe(15.0)
        ->and($config->userAgentSuffix())->toBe('integration-suite')
        ->and($config->userAgent())->toStartWith('creem-php-sdk/')
        ->and($config->userAgent())->toContain('php/'.PHP_VERSION)
        ->and($config->userAgent())->toEndWith('integration-suite');
});

test('config requires explicit opt-in for non official base url overrides', function (): void {
    $config = new Config(
        'sk_test_123',
        baseUrl: 'https://example.test/',
        allowUnsafeBaseUrlOverride: true,
    );

    expect($config->baseUrl())->toBe('https://example.test')
        ->and($config->resolveBaseUrl())->toBe('https://example.test');
});

foreach (invalidConfigValues() as $dataset => [$factory, $message]) {
    test("config rejects invalid values ({$dataset})", function () use ($factory, $message): void {
        expect($factory)->toThrow(InvalidArgumentException::class, $message);
    });
}

test('config normalizes blank user agent suffixes to null', function (): void {
    $config = new Config('sk_test_123', userAgentSuffix: '   ');

    expect($config->userAgentSuffix())->toBeNull()
        ->and($config->userAgent())->not->toEndWith(' ');
});

test('config strips control characters from the user agent suffix', function (): void {
    $config = new Config('sk_test_123', userAgentSuffix: " app\r\nv1\t ");

    expect($config->userAgentSuffix())->toBe('appv1')
        ->and($config->userAgent())->toEndWith('appv1');
});

test('config resolves the default environment base url when no override is provided', function (): void {
    $config = new Config('sk_test_123', Environment::Test);

    expect($config->baseUrl())->toBeNull()
        ->and($config->resolveBaseUrl())->toBe(Environment::Test->baseUrl());
});

test('config uses redacted values for debug output string casts and serialization', function (): void {
    $config = new Config('sk_test_secret_1234', userAgentSuffix: 'integration-suite');
    $debugInfo = $config->__debugInfo();
    $serialized = serialize($config);

    expect($debugInfo['apiKey'] ?? null)->toBe('sk_****1234')
        ->and((string) $config)->toContain('sk_****1234')
        ->and((string) $config)->not->toContain('sk_test_secret_1234')
        ->and($serialized)->toContain('sk_****1234')
        ->and($serialized)->not->toContain('sk_test_secret_1234');
});

test('config rejects unserialization to avoid restoring redacted credentials', function (): void {
    $config = new Config('sk_test_secret_1234');

    expect(static fn (): mixed => unserialize(serialize($config)))
        ->toThrow(LogicException::class, 'Unserializing Creem\\Config is not supported.');
});

/**
 * @return array<string, array{0: callable(): Config, 1: string}>
 */
function invalidConfigValues(): array
{
    return [
        'blank api key' => [
            static fn (): Config => new Config(''),
            'The Creem API key cannot be empty.',
        ],
        'zero timeout' => [
            static fn (): Config => new Config('sk_test_123', timeout: 0),
            'The Creem request timeout must be greater than zero.',
        ],
        'malformed api key' => [
            static fn (): Config => new Config('not-a-creem-key'),
            'The Creem API key must start with "sk_" or "creem_".',
        ],
        'negative timeout' => [
            static fn (): Config => new Config('sk_test_123', timeout: -1),
            'The Creem request timeout must be greater than zero.',
        ],
        'blank base url' => [
            static fn (): Config => new Config('sk_test_123', baseUrl: '   '),
            'The Creem base URL override cannot be blank.',
        ],
        'non https base url' => [
            static fn (): Config => new Config('sk_test_123', baseUrl: 'http://example.test'),
            'The Creem base URL override must be a valid HTTPS URL.',
        ],
        'untrusted base url without opt in' => [
            static fn (): Config => new Config('sk_test_123', baseUrl: 'https://example.test'),
            'The Creem base URL override host is not trusted. Pass allowUnsafeBaseUrlOverride: true to allow non-Creem hosts.',
        ],
        'malformed base url' => [
            static fn (): Config => new Config('sk_test_123', baseUrl: 'not a url'),
            'The Creem base URL override must be a valid HTTPS URL.',
        ],
    ];
}
