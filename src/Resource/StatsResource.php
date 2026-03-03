<?php

declare(strict_types=1);

namespace Creem\Resource;

use Creem\Dto\Stats\GetStatsSummaryRequest;
use Creem\Dto\Stats\StatsSummary;
use Creem\Internal\Http\Requests\Stats\GetMetricsSummaryRequest;

final class StatsResource extends Resource
{
    public function summary(GetStatsSummaryRequest $request): StatsSummary
    {
        return StatsSummary::fromPayload($this->send(new GetMetricsSummaryRequest($request->toQuery())));
    }
}
