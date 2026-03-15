<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Transaction;

use Antoniadisio\Creem\Enum\ApiMode;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\TransactionStatus;
use Antoniadisio\Creem\Enum\TransactionType;
use Antoniadisio\Creem\Internal\Hydration\Payload;

final readonly class Transaction
{
    public function __construct(
        public ?string $id,
        public ?ApiMode $mode,
        public ?string $object,
        public ?int $amount,
        public ?int $amountPaid,
        public ?int $discountAmount,
        public ?CurrencyCode $currency,
        public ?TransactionType $type,
        public ?string $taxCountry,
        public ?int $taxAmount,
        public ?TransactionStatus $status,
        public ?int $refundedAmount,
        public ?string $order,
        public ?string $subscription,
        public ?string $customer,
        public ?string $description,
        public ?int $periodStart,
        public ?int $periodEnd,
        public ?int $createdAt,
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
