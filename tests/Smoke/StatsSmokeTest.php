<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Smoke;

use Antoniadisio\Creem\Dto\Stats\GetStatsSummaryRequest;
use Antoniadisio\Creem\Dto\Stats\StatsSummary;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\StatsInterval;
use Antoniadisio\Creem\Tests\SmokeTestCase;

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
