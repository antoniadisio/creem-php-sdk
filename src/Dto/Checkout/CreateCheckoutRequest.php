<?php

declare(strict_types=1);

namespace Creem\Dto\Checkout;

use function array_filter;

final class CreateCheckoutRequest
{
    /**
     * @param  list<array<string, mixed>>  $customFields
     * @param  list<array<string, mixed>>  $customField
     * @param  array<string, mixed>|null  $customer
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public readonly string $productId,
        public readonly ?string $requestId = null,
        public readonly int|float|null $units = null,
        public readonly ?string $discountCode = null,
        public readonly ?array $customer = null,
        public readonly array $customFields = [],
        public readonly array $customField = [],
        public readonly ?string $successUrl = null,
        public readonly ?array $metadata = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'request_id' => $this->requestId,
            'product_id' => $this->productId,
            'units' => $this->units,
            'discount_code' => $this->discountCode,
            'customer' => $this->customer,
            'custom_fields' => $this->customFields,
            'custom_field' => $this->customField,
            'success_url' => $this->successUrl,
            'metadata' => $this->metadata,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
