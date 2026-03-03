<?php

declare(strict_types=1);

namespace Creem\Dto\Transaction;

use Creem\Enum\ApiMode;
use Creem\Enum\CurrencyCode;
use Creem\Enum\TransactionStatus;
use Creem\Enum\TransactionType;
use Creem\Internal\Hydration\Payload;

final class Transaction
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?ApiMode $mode,
        public readonly ?string $object,
        public readonly ?int $amount,
        public readonly ?int $amountPaid,
        public readonly ?int $discountAmount,
        public readonly ?CurrencyCode $currency,
        public readonly ?TransactionType $type,
        public readonly ?string $taxCountry,
        public readonly ?int $taxAmount,
        public readonly ?TransactionStatus $status,
        public readonly ?int $refundedAmount,
        public readonly ?string $order,
        public readonly ?string $subscription,
        public readonly ?string $customer,
        public readonly ?string $description,
        public readonly ?int $periodStart,
        public readonly ?int $periodEnd,
        public readonly ?int $createdAt,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            Payload::string($payload, 'id', self::class, true),
            Payload::enum($payload, 'mode', self::class, ApiMode::class, true),
            Payload::string($payload, 'object', self::class, true),
            Payload::integer($payload, 'amount', self::class, true),
            Payload::integer($payload, 'amount_paid', self::class),
            Payload::integer($payload, 'discount_amount', self::class),
            Payload::enum($payload, 'currency', self::class, CurrencyCode::class, true),
            Payload::enum($payload, 'type', self::class, TransactionType::class, true),
            Payload::string($payload, 'tax_country', self::class),
            Payload::integer($payload, 'tax_amount', self::class),
            Payload::enum($payload, 'status', self::class, TransactionStatus::class, true),
            Payload::integer($payload, 'refunded_amount', self::class),
            Payload::string($payload, 'order', self::class),
            Payload::string($payload, 'subscription', self::class),
            Payload::string($payload, 'customer', self::class),
            Payload::string($payload, 'description', self::class),
            Payload::integer($payload, 'period_start', self::class),
            Payload::integer($payload, 'period_end', self::class),
            Payload::integer($payload, 'created_at', self::class, true),
        );
    }
}
