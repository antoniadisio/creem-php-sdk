<?php

declare(strict_types=1);

namespace Creem\Dto\Product;

use Creem\Dto\Common\CustomFieldInput;
use Creem\Enum\BillingPeriod;
use Creem\Enum\BillingType;
use Creem\Enum\CurrencyCode;
use Creem\Enum\TaxCategory;
use Creem\Enum\TaxMode;
use Creem\Internal\Serialization\RequestValueNormalizer;

final readonly class CreateProductRequest
{
    /**
     * @param  list<CustomFieldInput>  $customFields
     */
    public function __construct(
        public string $name,
        public int $price,
        public CurrencyCode $currency,
        public BillingType $billingType,
        public ?string $description = null,
        public ?string $imageUrl = null,
        public ?BillingPeriod $billingPeriod = null,
        public ?TaxMode $taxMode = null,
        public ?TaxCategory $taxCategory = null,
        public ?string $defaultSuccessUrl = null,
        public array $customFields = [],
        public ?bool $abandonedCartRecoveryEnabled = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return RequestValueNormalizer::payload([
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
            'price' => $this->price,
            'currency' => $this->currency,
            'billing_type' => $this->billingType,
            'billing_period' => $this->billingPeriod,
            'tax_mode' => $this->taxMode,
            'tax_category' => $this->taxCategory,
            'default_success_url' => $this->defaultSuccessUrl,
            'custom_fields' => $this->customFields,
            'abandoned_cart_recovery_enabled' => $this->abandonedCartRecoveryEnabled,
        ]);
    }
}
