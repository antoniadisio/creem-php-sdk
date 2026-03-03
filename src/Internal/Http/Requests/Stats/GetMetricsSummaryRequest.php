<?php

declare(strict_types=1);

namespace Creem\Internal\Http\Requests\Stats;

use Creem\Internal\Http\Requests\QueryRequest;
use Saloon\Enums\Method;

final class GetMetricsSummaryRequest extends QueryRequest
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/v1/stats/summary';
    }
}
