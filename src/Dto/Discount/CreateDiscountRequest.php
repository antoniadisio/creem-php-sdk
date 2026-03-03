<?php

declare(strict_types=1);

namespace Creem\Dto\Discount;

use function array_filter;

final class CreateDiscountRequest
{
    /**
     * @param  list<string>  $appliesToProducts
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $duration,
        public readonly array $appliesToProducts,
        public readonly ?string $code = null,
        public readonly int|float|null $amount = null,
        public readonly ?string $currency = null,
        public readonly int|float|null $percentage = null,
        public readonly ?string $expiryDate = null,
        public readonly int|float|null $maxRedemptions = null,
        public readonly int|float|null $durationInMonths = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'percentage' => $this->percentage,
            'expiry_date' => $this->expiryDate,
            'max_redemptions' => $this->maxRedemptions,
            'duration' => $this->duration,
            'duration_in_months' => $this->durationInMonths,
            'applies_to_products' => $this->appliesToProducts,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
