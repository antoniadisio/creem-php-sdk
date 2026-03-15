<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Stats;

use Antoniadisio\Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

final readonly class StatsPeriod
{
    public function __construct(
        public ?DateTimeImmutable $timestamp,
        public ?int $grossRevenue,
        public ?int $netRevenue,
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
