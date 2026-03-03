<?php

declare(strict_types=1);

namespace Creem\Dto\Stats;

use Creem\Dto\Common\StructuredList;
use Creem\Dto\Common\StructuredObject;
use Creem\Internal\Hydration\Payload;

final class StatsSummary
{
    public function __construct(
        public readonly ?StructuredObject $totals,
        public readonly StructuredList $periods,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            Payload::object($payload, 'totals'),
            Payload::list($payload, 'periods'),
        );
    }
}
