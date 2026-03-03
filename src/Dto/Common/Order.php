<?php

declare(strict_types=1);

namespace Creem\Dto\Common;

use Creem\Enum\ApiMode;
use Creem\Enum\CurrencyCode;
use Creem\Enum\OrderStatus;
use Creem\Enum\OrderType;
use Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

final class Order
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?ApiMode $mode,
        public readonly ?string $object,
        public readonly ?string $customer,
        public readonly ?string $product,
        public readonly ?string $transaction,
        public readonly ?string $discount,
        public readonly ?int $amount,
        public readonly ?int $subTotal,
        public readonly ?int $taxAmount,
        public readonly ?int $discountAmount,
        public readonly ?int $amountDue,
        public readonly ?int $amountPaid,
        public readonly ?CurrencyCode $currency,
        public readonly ?float $fxAmount,
        public readonly ?CurrencyCode $fxCurrency,
        public readonly ?float $fxRate,
        public readonly ?OrderStatus $status,
        public readonly ?OrderType $type,
        public readonly ?string $affiliate,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $updatedAt,
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
            Payload::string($payload, 'customer', self::class),
            Payload::string($payload, 'product', self::class, true),
            Payload::string($payload, 'transaction', self::class),
            Payload::string($payload, 'discount', self::class),
            Payload::integer($payload, 'amount', self::class, true),
            Payload::integer($payload, 'sub_total', self::class),
            Payload::integer($payload, 'tax_amount', self::class),
            Payload::integer($payload, 'discount_amount', self::class),
            Payload::integer($payload, 'amount_due', self::class),
            Payload::integer($payload, 'amount_paid', self::class),
            Payload::enum($payload, 'currency', self::class, CurrencyCode::class, true),
            Payload::decimal($payload, 'fx_amount', self::class),
            Payload::enum($payload, 'fx_currency', self::class, CurrencyCode::class),
            Payload::decimal($payload, 'fx_rate', self::class),
            Payload::enum($payload, 'status', self::class, OrderStatus::class, true),
            Payload::enum($payload, 'type', self::class, OrderType::class, true),
            Payload::string($payload, 'affiliate', self::class),
            Payload::dateTime($payload, 'created_at', self::class, true),
            Payload::dateTime($payload, 'updated_at', self::class, true),
        );
    }
}
