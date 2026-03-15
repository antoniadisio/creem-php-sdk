<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Stats;

use Antoniadisio\Creem\Exception\HydrationException;
use Antoniadisio\Creem\Internal\Hydration\Payload;

use function array_is_list;
use function is_array;

final readonly class StatsSummary
{
    /**
     * @param  list<StatsPeriod>  $periods
     */
    public function __construct(
        public ?StatsTotals $totals,
        public array $periods,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            Payload::typedObject(
                $payload,
                'totals',
                self::class,
                static fn (array $value): StatsTotals => StatsTotals::fromPayload($value),
                true,
            ),
            Payload::typedList(
                $payload,
                'periods',
                self::class,
                static function (mixed $item): StatsPeriod {
                    if (! is_array($item) || array_is_list($item)) {
                        throw HydrationException::invalidField(self::class, 'periods', 'object', $item);
                    }

                    /** @var array<string, mixed> $item */
                    return StatsPeriod::fromPayload($item);
                },
            ),
        );
    }
}
