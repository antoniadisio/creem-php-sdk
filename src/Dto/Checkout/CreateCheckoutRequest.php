<?php

declare(strict_types=1);

namespace Creem\Dto\Checkout;

use Creem\Dto\Common\CustomFieldInput;
use Creem\Internal\Serialization\RequestValueNormalizer;

final readonly class CreateCheckoutRequest
{
    /**
     * @param  list<CustomFieldInput>  $customFields
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public string $productId,
        public ?string $requestId = null,
        public ?int $units = null,
        public ?string $discountCode = null,
        public ?CheckoutCustomerInput $customer = null,
        public array $customFields = [],
        public ?string $successUrl = null,
        public ?array $metadata = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return RequestValueNormalizer::payload([
            'request_id' => $this->requestId,
            'product_id' => $this->productId,
            'units' => $this->units,
            'discount_code' => $this->discountCode,
            'customer' => $this->customer,
            'custom_fields' => $this->customFields,
            'success_url' => $this->successUrl,
            'metadata' => $this->metadata,
        ]);
    }
}
