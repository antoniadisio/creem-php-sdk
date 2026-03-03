<?php

declare(strict_types=1);

namespace Creem\Dto\Transaction;

use Creem\Internal\Hydration\Payload;

final class Transaction
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $mode,
        public readonly ?string $object,
        public readonly int|float|null $amount,
        public readonly int|float|null $amountPaid,
        public readonly int|float|null $discountAmount,
        public readonly ?string $currency,
        public readonly ?string $type,
        public readonly ?string $taxCountry,
        public readonly int|float|null $taxAmount,
        public readonly ?string $status,
        public readonly int|float|null $refundedAmount,
        public readonly ?string $order,
        public readonly ?string $subscription,
        public readonly ?string $customer,
        public readonly ?string $description,
        public readonly int|float|null $periodStart,
        public readonly int|float|null $periodEnd,
        public readonly int|float|null $createdAt,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            Payload::string($payload, 'id'),
            Payload::string($payload, 'mode'),
            Payload::string($payload, 'object'),
            Payload::number($payload, 'amount'),
            Payload::number($payload, 'amount_paid'),
            Payload::number($payload, 'discount_amount'),
            Payload::string($payload, 'currency'),
            Payload::string($payload, 'type'),
            Payload::string($payload, 'tax_country'),
            Payload::number($payload, 'tax_amount'),
            Payload::string($payload, 'status'),
            Payload::number($payload, 'refunded_amount'),
            Payload::string($payload, 'order'),
            Payload::string($payload, 'subscription'),
            Payload::string($payload, 'customer'),
            Payload::string($payload, 'description'),
            Payload::number($payload, 'period_start'),
            Payload::number($payload, 'period_end'),
            Payload::number($payload, 'created_at'),
        );
    }
}
