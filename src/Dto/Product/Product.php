<?php

declare(strict_types=1);

namespace Creem\Dto\Product;

use Creem\Dto\Common\StructuredList;
use Creem\Internal\Hydration\Payload;

final class Product
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $mode,
        public readonly ?string $object,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?string $imageUrl,
        public readonly StructuredList $features,
        public readonly int|float|null $price,
        public readonly ?string $currency,
        public readonly ?string $billingType,
        public readonly ?string $billingPeriod,
        public readonly ?string $status,
        public readonly ?string $taxMode,
        public readonly ?string $taxCategory,
        public readonly ?string $productUrl,
        public readonly ?string $defaultSuccessUrl,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
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
            Payload::string($payload, 'name'),
            Payload::string($payload, 'description'),
            Payload::string($payload, 'image_url'),
            Payload::list($payload, 'features'),
            Payload::number($payload, 'price'),
            Payload::string($payload, 'currency'),
            Payload::string($payload, 'billing_type'),
            Payload::string($payload, 'billing_period'),
            Payload::string($payload, 'status'),
            Payload::string($payload, 'tax_mode'),
            Payload::string($payload, 'tax_category'),
            Payload::string($payload, 'product_url'),
            Payload::string($payload, 'default_success_url'),
            Payload::string($payload, 'created_at'),
            Payload::string($payload, 'updated_at'),
        );
    }
}
