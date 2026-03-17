<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Config;
use Antoniadisio\Creem\CredentialProfile;
use Antoniadisio\Creem\CredentialProfiles;
use Antoniadisio\Creem\Enum\Environment;
use InvalidArgumentException;
use LogicException;

test('credential profile exposes config-backed values and normalizes blank webhook secrets', function (): void {
    $profile = new CredentialProfile(
        apiKey: '  creem_test_123  ',
        environment: Environment::Test,
        baseUrl: 'https://test-api.creem.io/',
        timeout: 15,
        userAgentSuffix: '  integration-suite  ',
        webhookSecret: '   ',
    );

    expect($profile->config())->toBeInstanceOf(Config::class)
        ->and($profile->apiKey())->toBe('creem_test_123')
        ->and($profile->environment())->toBe(Environment::Test)
        ->and($profile->baseUrl())->toBe('https://test-api.creem.io')
        ->and($profile->timeout())->toBe(15.0)
        ->and($profile->userAgentSuffix())->toBe('integration-suite')
        ->and($profile->webhookSecret())->toBeNull()
        ->and($profile->hasWebhookSecret())->toBeFalse();
});

test('credential profile uses redacted values for debug output string casts and serialization', function (): void {
    $profile = new CredentialProfile(
        apiKey: 'creem_secret_1234',
        webhookSecret: 'whsec_test_secret_9876',
    );
    $debugInfo = $profile->__debugInfo();
    $serialized = serialize($profile);
    $configDebugInfo = $debugInfo['config'] ?? null;

    expect($configDebugInfo)->toBeArray();

    if (! is_array($configDebugInfo)) {
        return;
    }

    expect($configDebugInfo['apiKey'] ?? null)->toBe('creem_****1234')
        ->and($debugInfo['webhookSecret'] ?? null)->toBe('whsec_****9876')
        ->and((string) $profile)->toContain('creem_****1234')
        ->and((string) $profile)->toContain('whsec_****9876')
        ->and((string) $profile)->not->toContain('creem_secret_1234')
        ->and((string) $profile)->not->toContain('whsec_test_secret_9876')
        ->and($serialized)->toContain('creem_****1234')
        ->and($serialized)->toContain('whsec_****9876')
        ->and($serialized)->not->toContain('creem_secret_1234')
        ->and($serialized)->not->toContain('whsec_test_secret_9876');
});

test('credential profile rejects unserialization to avoid restoring redacted credentials', function (): void {
    $profile = new CredentialProfile(
        apiKey: 'creem_secret_1234',
        webhookSecret: 'whsec_test_secret_9876',
    );

    expect(static fn(): mixed => unserialize(serialize($profile)))
        ->toThrow(LogicException::class, 'Unserializing Creem\\CredentialProfile is not supported.');
});

test('credential profiles resolve named profiles configs and webhook secrets', function (): void {
    $profiles = new CredentialProfiles([
        'default' => new CredentialProfile(
            apiKey: 'creem_test_default_1234',
            environment: Environment::Test,
            webhookSecret: 'whsec_default_1234',
        ),
        'playground' => new CredentialProfile(
            apiKey: 'creem_playground_5678',
            environment: Environment::Production,
            webhookSecret: 'whsec_playground_5678',
        ),
    ]);

    expect($profiles->names())->toBe(['default', 'playground'])
        ->and($profiles->hasProfile('default'))->toBeTrue()
        ->and($profiles->config('default')->apiKey())->toBe('creem_test_default_1234')
        ->and($profiles->config('playground')->environment())->toBe(Environment::Production)
        ->and($profiles->webhookSecret('playground'))->toBe('whsec_playground_5678');
});

test('credential profiles use redacted values for debug output string casts and serialization', function (): void {
    $profiles = new CredentialProfiles([
        'default' => new CredentialProfile(
            apiKey: 'creem_secret_1234',
            webhookSecret: 'whsec_test_secret_9876',
        ),
    ]);
    $debugInfo = $profiles->__debugInfo();
    $serialized = serialize($profiles);

    expect($debugInfo['count'] ?? null)->toBe(1)
        ->and($debugInfo['names'] ?? null)->toBe(['default'])
        ->and((string) $profiles)->toContain('default')
        ->and($serialized)->toContain('creem_****1234')
        ->and($serialized)->toContain('whsec_****9876')
        ->and($serialized)->not->toContain('creem_secret_1234')
        ->and($serialized)->not->toContain('whsec_test_secret_9876');
});

test('credential profiles reject unserialization to avoid restoring redacted credentials', function (): void {
    $profiles = new CredentialProfiles([
        'default' => new CredentialProfile(
            apiKey: 'creem_secret_1234',
            webhookSecret: 'whsec_test_secret_9876',
        ),
    ]);

    expect(static fn(): mixed => unserialize(serialize($profiles)))
        ->toThrow(LogicException::class, 'Unserializing Creem\\CredentialProfiles is not supported.');
});

foreach (invalidCredentialProfileSets() as $dataset => [$factory, $message]) {
    test("credential profiles reject invalid configuration ({$dataset})", function () use ($factory, $message): void {
        expect($factory)->toThrow(InvalidArgumentException::class, $message);
    });
}

/**
 * @return array<string, array{0: callable(): CredentialProfiles, 1: string}>
 */
function invalidCredentialProfileSets(): array
{
    return [
        'empty profiles' => [
            static fn(): CredentialProfiles => new CredentialProfiles([]),
            'At least one Creem credential profile is required.',
        ],
        'blank name' => [
            static fn(): CredentialProfiles => new CredentialProfiles([
                '   ' => new CredentialProfile('creem_123'),
            ]),
            'Credential profile names cannot be blank.',
        ],
        'invalid characters' => [
            static fn(): CredentialProfiles => new CredentialProfiles([
                'merchant/main' => new CredentialProfile('creem_123'),
            ]),
            'Credential profile names must start with an alphanumeric character and contain only letters, numbers, dots, underscores, and dashes.',
        ],
        'unknown profile lookup' => [
            static function (): CredentialProfiles {
                $profiles = new CredentialProfiles([
                    'default' => new CredentialProfile('creem_123'),
                ]);

                $profiles->profile('missing');

                return $profiles;
            },
            'Unknown Creem credential profile [missing].',
        ],
        'missing webhook secret' => [
            static function (): CredentialProfiles {
                $profiles = new CredentialProfiles([
                    'default' => new CredentialProfile('creem_123'),
                ]);

                $profiles->webhookSecret('default');

                return $profiles;
            },
            'The Creem credential profile [default] does not define a webhook secret.',
        ],
    ];
}
