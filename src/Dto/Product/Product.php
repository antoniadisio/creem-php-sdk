<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Product;

use Antoniadisio\Creem\Dto\Common\CustomField;
use Antoniadisio\Creem\Dto\Common\ProductFeature;
use Antoniadisio\Creem\Enum\ApiMode;
use Antoniadisio\Creem\Enum\BillingPeriod;
use Antoniadisio\Creem\Enum\BillingType;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\ProductStatus;
use Antoniadisio\Creem\Enum\TaxCategory;
use Antoniadisio\Creem\Enum\TaxMode;
use Antoniadisio\Creem\Exception\HydrationException;
use Antoniadisio\Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

use function array_is_list;
use function is_array;

final readonly class Product
{
    /**
     * @param  list<ProductFeature>  $features
     * @param  list<CustomField>  $customFields
     */
    public function __construct(
        public ?string $id,
        public ?ApiMode $mode,
        public ?string $object,
        public ?string $name,
        public ?string $description,
        public ?string $imageUrl,
        public array $features,
        public ?int $price,
        public ?CurrencyCode $currency,
        public ?BillingType $billingType,
        public ?BillingPeriod $billingPeriod,
        public ?ProductStatus $status,
        public ?TaxMode $taxMode,
        public ?TaxCategory $taxCategory,
        public ?string $productUrl,
        public ?string $defaultSuccessUrl,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
        public array $customFields = [],
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
            Payload::typedList(
                $payload,
                'custom_fields',
                self::class,
                static function (mixed $item): CustomField {
                    if (! is_array($item) || array_is_list($item)) {
                        throw HydrationException::invalidField(self::class, 'custom_fields', 'object', $item);
                    }

                    /** @var array<string, mixed> $item */
                    return CustomField::fromPayload($item);
                },
            ),
        );
    }
}
