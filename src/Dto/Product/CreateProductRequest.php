<?php

declare(strict_types=1);

namespace Creem\Dto\Product;

use function array_filter;

final class CreateProductRequest
{
    /**
     * @param  list<array<string, mixed>>  $customFields
     * @param  list<array<string, mixed>>  $customField
     */
    public function __construct(
        public readonly string $name,
        public readonly int|float $price,
        public readonly string $currency,
        public readonly string $billingType,
        public readonly ?string $description = null,
        public readonly ?string $imageUrl = null,
        public readonly ?string $billingPeriod = null,
        public readonly ?string $taxMode = null,
        public readonly ?string $taxCategory = null,
        public readonly ?string $defaultSuccessUrl = null,
        public readonly array $customFields = [],
        public readonly array $customField = [],
        public readonly ?bool $abandonedCartRecoveryEnabled = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
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
            'custom_field' => $this->customField,
            'abandoned_cart_recovery_enabled' => $this->abandonedCartRecoveryEnabled,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
