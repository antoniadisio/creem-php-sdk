<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Discount;

use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\DiscountDuration;
use Antoniadisio\Creem\Enum\DiscountType;
use Antoniadisio\Creem\Internal\Serialization\RequestValueNormalizer;
use DateTimeImmutable;
use InvalidArgumentException;

use function array_is_list;
use function get_debug_type;
use function is_string;
use function sprintf;
use function trim;

final readonly class CreateDiscountRequest
{
    public string $name;

    public DiscountType $type;

    public DiscountDuration $duration;

    /**
     * @var list<string>
     */
    public array $appliesToProducts;

    public ?string $code;

    public ?int $amount;

    public ?CurrencyCode $currency;

    public ?int $percentage;

    public ?int $maxRedemptions;

    public ?int $durationInMonths;

    /**
     * @param  array<array-key, mixed>  $appliesToProducts
     */
    public function __construct(
        string $name,
        DiscountType $type,
        DiscountDuration $duration,
        array $appliesToProducts,
        ?string $code = null,
        ?int $amount = null,
        ?CurrencyCode $currency = null,
        ?int $percentage = null,
        public ?DateTimeImmutable $expiryDate = null,
        ?int $maxRedemptions = null,
        ?int $durationInMonths = null,
    ) {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('The discount name cannot be blank.');
        }

        if ($code !== null) {
            $code = trim($code);

            if ($code === '') {
                throw new InvalidArgumentException('The discount code cannot be blank.');
            }
        }

        if (! array_is_list($appliesToProducts)) {
            throw new InvalidArgumentException('The discount applies-to products value must be a list.');
        }

        foreach ($appliesToProducts as $index => $productId) {
            if (! is_string($productId)) {
                throw new InvalidArgumentException(sprintf(
                    'Discount product ID at index %d must be a string, %s given.',
                    $index,
                    get_debug_type($productId),
                ));
            }

            $productId = trim($productId);

            if ($productId === '') {
                throw new InvalidArgumentException(sprintf(
                    'Discount product ID at index %d cannot be blank.',
                    $index,
                ));
            }

            $appliesToProducts[$index] = $productId;
        }

        if ($maxRedemptions !== null && $maxRedemptions <= 0) {
            throw new InvalidArgumentException('The discount max redemptions must be greater than zero.');
        }

        if ($durationInMonths !== null && $durationInMonths <= 0) {
            throw new InvalidArgumentException('The discount duration in months must be greater than zero.');
        }

        if ($duration === DiscountDuration::Repeating && $durationInMonths === null) {
            throw new InvalidArgumentException('The discount duration in months is required for repeating discounts.');
        }

        if ($duration !== DiscountDuration::Repeating && $durationInMonths !== null) {
            throw new InvalidArgumentException('The discount duration in months is only allowed for repeating discounts.');
        }

        if ($type === DiscountType::Fixed) {
            if ($amount === null || $amount <= 0) {
                throw new InvalidArgumentException('The fixed discount amount must be greater than zero.');
            }

            if (! $currency instanceof CurrencyCode) {
                throw new InvalidArgumentException('The fixed discount currency is required.');
            }

            if ($percentage !== null) {
                throw new InvalidArgumentException('The fixed discount percentage must be omitted.');
            }
        }

        if ($type === DiscountType::Percentage) {
            if ($percentage === null || $percentage <= 0 || $percentage > 100) {
                throw new InvalidArgumentException('The percentage discount value must be between 1 and 100.');
            }

            if ($amount !== null) {
                throw new InvalidArgumentException('The percentage discount amount must be omitted.');
            }

            if ($currency instanceof \Antoniadisio\Creem\Enum\CurrencyCode) {
                throw new InvalidArgumentException('The percentage discount currency must be omitted.');
            }
        }

        $this->name = $name;
        $this->type = $type;
        $this->duration = $duration;
        $this->appliesToProducts = $appliesToProducts;
        $this->code = $code;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->percentage = $percentage;
        $this->maxRedemptions = $maxRedemptions;
        $this->durationInMonths = $durationInMonths;
    }

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
