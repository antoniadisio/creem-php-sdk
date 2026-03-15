<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Discount;

use Antoniadisio\Creem\Enum\ApiMode;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\DiscountDuration;
use Antoniadisio\Creem\Enum\DiscountStatus;
use Antoniadisio\Creem\Enum\DiscountType;
use Antoniadisio\Creem\Exception\HydrationException;
use Antoniadisio\Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

final readonly class Discount
{
    /**
     * @param  list<string>  $appliesToProducts
     */
    public function __construct(
        public ?string $id,
        public ?ApiMode $mode,
        public ?string $object,
        public ?DiscountStatus $status,
        public ?string $name,
        public ?string $code,
        public ?DiscountType $type,
        public ?int $amount,
        public ?CurrencyCode $currency,
        public ?int $percentage,
        public ?DateTimeImmutable $expiryDate,
        public ?int $maxRedemptions,
        public ?DiscountDuration $duration,
        public ?int $durationInMonths,
        public array $appliesToProducts,
        public ?int $redeemCount,
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
