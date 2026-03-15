<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Stats;

use Antoniadisio\Creem\Internal\Hydration\Payload;

final readonly class StatsTotals
{
    public function __construct(
        public ?int $totalProducts,
        public ?int $totalSubscriptions,
        public ?int $totalCustomers,
        public ?int $totalPayments,
        public ?int $activeSubscriptions,
        public ?int $totalRevenue,
        public ?int $totalNetRevenue,
        public ?int $netMonthlyRecurringRevenue,
        public ?int $monthlyRecurringRevenue,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            Payload::integer($payload, 'totalProducts', self::class, true),
            Payload::integer($payload, 'totalSubscriptions', self::class, true),
            Payload::integer($payload, 'totalCustomers', self::class, true),
            Payload::integer($payload, 'totalPayments', self::class, true),
            Payload::integer($payload, 'activeSubscriptions', self::class, true),
            Payload::integer($payload, 'totalRevenue', self::class, true),
            Payload::integer($payload, 'totalNetRevenue', self::class, true),
            Payload::integer($payload, 'netMonthlyRecurringRevenue', self::class, true),
            Payload::integer($payload, 'monthlyRecurringRevenue', self::class, true),
        );
    }
}
