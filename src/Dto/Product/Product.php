<?php

declare(strict_types=1);

namespace Creem\Dto\Product;

use Creem\Dto\Common\ProductFeature;
use Creem\Enum\ApiMode;
use Creem\Enum\BillingPeriod;
use Creem\Enum\BillingType;
use Creem\Enum\CurrencyCode;
use Creem\Enum\ProductStatus;
use Creem\Enum\TaxCategory;
use Creem\Enum\TaxMode;
use Creem\Exception\HydrationException;
use Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

use function array_is_list;
use function is_array;

final class Product
{
    /**
     * @param  list<ProductFeature>  $features
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?ApiMode $mode,
        public readonly ?string $object,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?string $imageUrl,
        public readonly array $features,
        public readonly ?int $price,
        public readonly ?CurrencyCode $currency,
        public readonly ?BillingType $billingType,
        public readonly ?BillingPeriod $billingPeriod,
        public readonly ?ProductStatus $status,
        public readonly ?TaxMode $taxMode,
        public readonly ?TaxCategory $taxCategory,
        public readonly ?string $productUrl,
        public readonly ?string $defaultSuccessUrl,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $updatedAt,
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
            Payload::string($payload, 'name', self::class, true),
            Payload::string($payload, 'description', self::class, true),
            Payload::string($payload, 'image_url', self::class),
            Payload::typedList(
                $payload,
                'features',
                self::class,
                static function (mixed $item): ProductFeature {
                    if (! is_array($item) || array_is_list($item)) {
                        throw HydrationException::invalidField(self::class, 'features', 'object', $item);
                    }

                    /** @var array<string, mixed> $item */
                    return ProductFeature::fromCatalogPayload($item);
                },
            ),
            Payload::integer($payload, 'price', self::class, true),
            Payload::enum($payload, 'currency', self::class, CurrencyCode::class, true),
            Payload::enum($payload, 'billing_type', self::class, BillingType::class, true),
            Payload::enum($payload, 'billing_period', self::class, BillingPeriod::class, true),
            Payload::enum($payload, 'status', self::class, ProductStatus::class, true),
            Payload::enum($payload, 'tax_mode', self::class, TaxMode::class, true),
            Payload::enum($payload, 'tax_category', self::class, TaxCategory::class, true),
            Payload::string($payload, 'product_url', self::class),
            Payload::string($payload, 'default_success_url', self::class),
            Payload::dateTime($payload, 'created_at', self::class, true),
            Payload::dateTime($payload, 'updated_at', self::class, true),
        );
    }
}
