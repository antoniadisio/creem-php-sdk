<?php

declare(strict_types=1);

namespace Creem\Dto\Discount;

use Creem\Dto\Common\StructuredList;
use Creem\Internal\Hydration\Payload;

final class Discount
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $mode,
        public readonly ?string $object,
        public readonly ?string $status,
        public readonly ?string $name,
        public readonly ?string $code,
        public readonly ?string $type,
        public readonly int|float|null $amount,
        public readonly ?string $currency,
        public readonly int|float|null $percentage,
        public readonly ?string $expiryDate,
        public readonly int|float|null $maxRedemptions,
        public readonly ?string $duration,
        public readonly int|float|null $durationInMonths,
        public readonly StructuredList $appliesToProducts,
        public readonly int|float|null $redeemCount,
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
            Payload::string($payload, 'status'),
            Payload::string($payload, 'name'),
            Payload::string($payload, 'code'),
            Payload::string($payload, 'type'),
            Payload::number($payload, 'amount'),
            Payload::string($payload, 'currency'),
            Payload::number($payload, 'percentage'),
            Payload::string($payload, 'expiry_date'),
            Payload::number($payload, 'max_redemptions'),
            Payload::string($payload, 'duration'),
            Payload::number($payload, 'duration_in_months'),
            Payload::list($payload, 'applies_to_products'),
            Payload::number($payload, 'redeem_count'),
        );
    }
}
