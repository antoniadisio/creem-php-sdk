<?php

declare(strict_types=1);

namespace Creem\Dto\Discount;

use Creem\Enum\CurrencyCode;
use Creem\Enum\DiscountDuration;
use Creem\Enum\DiscountType;
use Creem\Internal\Serialization\RequestValueNormalizer;
use DateTimeImmutable;

final readonly class CreateDiscountRequest
{
    /**
     * @param  list<string>  $appliesToProducts
     */
    public function __construct(
        public string $name,
        public DiscountType $type,
        public DiscountDuration $duration,
        public array $appliesToProducts,
        public ?string $code = null,
        public ?int $amount = null,
        public ?CurrencyCode $currency = null,
        public ?int $percentage = null,
        public ?DateTimeImmutable $expiryDate = null,
        public ?int $maxRedemptions = null,
        public ?int $durationInMonths = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return RequestValueNormalizer::payload([
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'percentage' => $this->percentage,
            'expiry_date' => RequestValueNormalizer::rfc3339($this->expiryDate),
            'max_redemptions' => $this->maxRedemptions,
            'duration' => $this->duration,
            'duration_in_months' => $this->durationInMonths,
            'applies_to_products' => $this->appliesToProducts,
        ]);
    }
}
