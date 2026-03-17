<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\ClientFactory;
use Antoniadisio\Creem\CredentialProfile;
use Antoniadisio\Creem\CredentialProfiles;
use Antoniadisio\Creem\Enum\Environment;

test('client factory caches clients per profile and keeps configs distinct', function (): void {
    $profiles = new CredentialProfiles([
        'default' => new CredentialProfile(
            apiKey: 'creem_test_default_1234',
            environment: Environment::Test,
        ),
        'playground' => new CredentialProfile(
            apiKey: 'creem_playground_5678',
            environment: Environment::Production,
        ),
    ]);
    $factory = new ClientFactory($profiles);

    $defaultClient = $factory->forProfile('default');
    $playgroundClient = $factory->forProfile('playground');

    expect($factory->profiles())->toBe($profiles)
        ->and($defaultClient)->toBeInstanceOf(Client::class)
        ->and($playgroundClient)->toBeInstanceOf(Client::class)
        ->and($defaultClient)->toBe($factory->forProfile('default'))
        ->and($playgroundClient)->toBe($factory->forProfile('playground'))
        ->and($defaultClient)->not->toBe($playgroundClient)
        ->and($defaultClient->config()->apiKey())->toBe('creem_test_default_1234')
        ->and($defaultClient->config()->environment())->toBe(Environment::Test)
        ->and($playgroundClient->config()->apiKey())->toBe('creem_playground_5678')
        ->and($playgroundClient->config()->environment())->toBe(Environment::Production);
});
