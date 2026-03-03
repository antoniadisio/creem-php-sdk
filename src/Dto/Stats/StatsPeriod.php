<?php

declare(strict_types=1);

namespace Creem\Dto\Stats;

use Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

final class StatsPeriod
{
    public function __construct(
        public readonly ?DateTimeImmutable $timestamp,
        public readonly ?int $grossRevenue,
        public readonly ?int $netRevenue,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            Payload::millisecondsDateTime($payload, 'timestamp', self::class, true),
            Payload::integer($payload, 'grossRevenue', self::class, true),
            Payload::integer($payload, 'netRevenue', self::class, true),
        );
    }
}
