<?php

declare(strict_types=1);

namespace Creem\Tests;

use Creem\Client;
use Creem\Config;
use Creem\Enum\Environment;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

use function getenv;
use function is_numeric;
use function is_string;
use function trim;

abstract class LiveTestCase extends TestCase
{
    public function liveClient(#[\SensitiveParameter]
        ?string $apiKey = null): Client
    {
        return new Client(new Config(
            $apiKey ?? $this->requireLiveApiKey(),
            Environment::Test,
            $this->liveBaseUrl(),
            $this->liveTimeout(),
            'pest-live-smoke',
        ));
    }

    public function requireLiveApiKey(): string
    {
        $apiKey = $this->liveApiKey();

        if ($apiKey === null) {
            $this->markTestSkipped('Set CREEM_LIVE_API_KEY to run authenticated live smoke tests.');
        }

        return $apiKey;
    }

    /**
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    public function liveWindow(): array
    {
        $end = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return [
            $end->sub(new DateInterval('P1D')),
            $end,
        ];
    }

    private function liveApiKey(): ?string
    {
        $apiKey = getenv('CREEM_LIVE_API_KEY');

        if (! is_string($apiKey)) {
            return null;
        }

        $apiKey = trim($apiKey);

        return $apiKey !== '' ? $apiKey : null;
    }

    private function liveBaseUrl(): ?string
    {
        $baseUrl = getenv('CREEM_LIVE_BASE_URL');

        if (! is_string($baseUrl)) {
            return null;
        }

        $baseUrl = trim($baseUrl);

        return $baseUrl !== '' ? $baseUrl : null;
    }

    private function liveTimeout(): float
    {
        $timeout = getenv('CREEM_LIVE_TIMEOUT');

        if (! is_string($timeout)) {
            return 10.0;
        }

        $timeout = trim($timeout);

        if ($timeout === '' || ! is_numeric($timeout)) {
            return 10.0;
        }

        return (float) $timeout;
    }
}
