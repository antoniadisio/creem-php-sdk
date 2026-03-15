<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Common;

use Antoniadisio\Creem\Enum\ApiMode;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\OrderStatus;
use Antoniadisio\Creem\Enum\OrderType;
use Antoniadisio\Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

final readonly class Order
{
    public function __construct(
        public ?string $id,
        public ?ApiMode $mode,
        public ?string $object,
        public ?string $customer,
        public ?string $product,
        public ?string $transaction,
        public ?string $discount,
        public ?int $amount,
        public ?int $subTotal,
        public ?int $taxAmount,
        public ?int $discountAmount,
        public ?int $amountDue,
        public ?int $amountPaid,
        public ?CurrencyCode $currency,
        public ?float $fxAmount,
        public ?CurrencyCode $fxCurrency,
        public ?float $fxRate,
        public ?OrderStatus $status,
        public ?OrderType $type,
        public ?string $affiliate,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
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
