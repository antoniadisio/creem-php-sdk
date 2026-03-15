<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests;

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Config;
use Antoniadisio\Creem\Enum\Environment;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

use function getenv;
use function is_string;
use function trim;

abstract class SmokeTestCase extends TestCase
{
    public function smokeClient(#[\SensitiveParameter] ?string $apiKey = null): Client
    {
        return new Client(new Config(
            apiKey: $apiKey ?? $this->requireSmokeApiKey(),
            environment: Environment::Test,
            userAgentSuffix: 'pest-smoke',
        ));
    }

    public function requireSmokeApiKey(): string
    {
        $apiKey = $this->smokeApiKey();

        if ($apiKey === null) {
            $this->markTestSkipped('Set CREEM_TEST_API_KEY to run the live smoke canary against Environment::Test.');
        }

        return $apiKey;
    }

    /**
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    public function smokeWindow(): array
    {
        $end = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return [
            $end->sub(new DateInterval('P1D')),
            $end,
        ];
    }

    private function smokeApiKey(): ?string
    {
        return $this->env('CREEM_TEST_API_KEY');
    }

    private function env(string $name): ?string
    {
        $value = getenv($name);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
