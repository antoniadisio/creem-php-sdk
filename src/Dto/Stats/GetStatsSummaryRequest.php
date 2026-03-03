<?php

declare(strict_types=1);

namespace Creem\Dto\Stats;

use function array_filter;

final class GetStatsSummaryRequest
{
    public function __construct(
        public readonly string $currency,
        public readonly int|float|null $startDate = null,
        public readonly int|float|null $endDate = null,
        public readonly ?string $interval = null,
    ) {}

    /**
     * @return array<string, string|int|float>
     */
    public function toQuery(): array
    {
        /** @var array<string, string|int|float> */
        return array_filter([
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'interval' => $this->interval,
            'currency' => $this->currency,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
