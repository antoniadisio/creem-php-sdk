<?php

declare(strict_types=1);

namespace Creem\Dto\Stats;

use Creem\Internal\Hydration\Payload;

final class StatsTotals
{
    public function __construct(
        public readonly ?int $totalProducts,
        public readonly ?int $totalSubscriptions,
        public readonly ?int $totalCustomers,
        public readonly ?int $totalPayments,
        public readonly ?int $activeSubscriptions,
        public readonly ?int $totalRevenue,
        public readonly ?int $totalNetRevenue,
        public readonly ?int $netMonthlyRecurringRevenue,
        public readonly ?int $monthlyRecurringRevenue,
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
