<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Resource;

use Antoniadisio\Creem\Dto\Stats\GetStatsSummaryRequest;
use Antoniadisio\Creem\Dto\Stats\StatsSummary;
use Antoniadisio\Creem\Internal\Http\Requests\Stats\GetMetricsSummaryRequest;

final class StatsResource extends Resource
{
    public function summary(GetStatsSummaryRequest $request): StatsSummary
    {
        return StatsSummary::fromPayload($this->send(new GetMetricsSummaryRequest($request->toQuery())));
    }
}
