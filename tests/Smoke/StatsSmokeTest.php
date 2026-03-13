<?php

declare(strict_types=1);

namespace Creem\Tests\Smoke;

use Creem\Dto\Stats\GetStatsSummaryRequest;
use Creem\Dto\Stats\StatsSummary;
use Creem\Enum\CurrencyCode;
use Creem\Enum\StatsInterval;
use Creem\Tests\SmokeTestCase;

test('smoke returns a typed stats summary', function (): void {
    /** @var SmokeTestCase $this */
    $this->requireSmokeApiKey();
    [$start, $end] = $this->smokeWindow();
    $summary = $this->smokeClient()->stats()->summary(
        new GetStatsSummaryRequest(CurrencyCode::USD, $start, $end, StatsInterval::Day),
    );

    expect($summary)->toBeInstanceOf(StatsSummary::class)
        ->and($summary->totals)->not->toBeNull()
        ->and($summary->periods)->toBeArray();
});
