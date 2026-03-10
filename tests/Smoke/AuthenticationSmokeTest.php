<?php

declare(strict_types=1);

namespace Creem\Tests\Smoke;

use Creem\Dto\Stats\GetStatsSummaryRequest;
use Creem\Dto\Stats\StatsSummary;
use Creem\Enum\CurrencyCode;
use Creem\Enum\StatsInterval;
use Creem\Exception\AuthenticationException;
use Creem\Tests\SmokeTestCase;

test('smoke maps an invalid api key to an authentication exception', function (): void {
    /** @var SmokeTestCase $this */
    $this->requireSmokeApiKey();
    [$start, $end] = $this->smokeWindow();
    $client = $this->smokeClient('sk_test_invalid_pest_smoke');

    expect(static fn (): StatsSummary => $client->stats()->summary(
        new GetStatsSummaryRequest(CurrencyCode::USD, $start, $end, StatsInterval::Day),
    ))->toThrow(AuthenticationException::class);
});
