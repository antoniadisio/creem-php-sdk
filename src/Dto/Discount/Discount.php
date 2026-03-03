<?php

declare(strict_types=1);

namespace Creem\Dto\Discount;

use Creem\Enum\ApiMode;
use Creem\Enum\CurrencyCode;
use Creem\Enum\DiscountDuration;
use Creem\Enum\DiscountStatus;
use Creem\Enum\DiscountType;
use Creem\Exception\HydrationException;
use Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

final class Discount
{
    /**
     * @param  list<string>  $appliesToProducts
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?ApiMode $mode,
        public readonly ?string $object,
        public readonly ?DiscountStatus $status,
        public readonly ?string $name,
        public readonly ?string $code,
        public readonly ?DiscountType $type,
        public readonly ?int $amount,
        public readonly ?CurrencyCode $currency,
        public readonly ?int $percentage,
        public readonly ?DateTimeImmutable $expiryDate,
        public readonly ?int $maxRedemptions,
        public readonly ?DiscountDuration $duration,
        public readonly ?int $durationInMonths,
        public readonly array $appliesToProducts,
        public readonly ?int $redeemCount,
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
            Payload::enum($payload, 'status', self::class, DiscountStatus::class, true),
            Payload::string($payload, 'name', self::class, true),
            Payload::string($payload, 'code', self::class, true),
            Payload::enum($payload, 'type', self::class, DiscountType::class, true),
            Payload::integer($payload, 'amount', self::class),
            Payload::enum($payload, 'currency', self::class, CurrencyCode::class),
            Payload::integer($payload, 'percentage', self::class),
            Payload::dateTime($payload, 'expiry_date', self::class),
            Payload::integer($payload, 'max_redemptions', self::class),
            Payload::enum($payload, 'duration', self::class, DiscountDuration::class),
            Payload::integer($payload, 'duration_in_months', self::class),
            Payload::typedList(
                $payload,
                'applies_to_products',
                self::class,
                static function (mixed $item): string {
                    if (! is_string($item)) {
                        throw HydrationException::invalidField(self::class, 'applies_to_products', 'string', $item);
                    }

                    return $item;
                },
            ),
            Payload::integer($payload, 'redeem_count', self::class),
        );
    }
}
