<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Stats;

use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\StatsInterval;
use Antoniadisio\Creem\Internal\Serialization\RequestValueNormalizer;
use DateTimeImmutable;

final readonly class GetStatsSummaryRequest
{
    public function __construct(
        public CurrencyCode $currency,
        public ?DateTimeImmutable $startDate = null,
        public ?DateTimeImmutable $endDate = null,
        public ?StatsInterval $interval = null,
    ) {}

    /**
     * @return array<string, string|int>
     */
    public function toQuery(): array
    {
        /** @var array<string, string|int> */
        return RequestValueNormalizer::query([
            'startDate' => RequestValueNormalizer::unixMilliseconds($this->startDate),
            'endDate' => RequestValueNormalizer::unixMilliseconds($this->endDate),
            'interval' => $this->interval,
            'currency' => $this->currency,
        ]);
    }
}
