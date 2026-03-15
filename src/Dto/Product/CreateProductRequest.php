<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Product;

use Antoniadisio\Creem\Dto\Common\CustomFieldInput;
use Antoniadisio\Creem\Enum\BillingPeriod;
use Antoniadisio\Creem\Enum\BillingType;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\TaxCategory;
use Antoniadisio\Creem\Enum\TaxMode;
use Antoniadisio\Creem\Internal\Serialization\RequestValueNormalizer;
use InvalidArgumentException;

use function array_is_list;
use function get_debug_type;
use function sprintf;
use function trim;

final readonly class CreateProductRequest
{
    public string $name;

    public int $price;

    /**
     * @var list<CustomFieldInput>
     */
    public array $customFields;

    /**
     * @param  array<array-key, mixed>  $customFields
     */
    public function __construct(
        string $name,
        int $price,
        public CurrencyCode $currency,
        public BillingType $billingType,
        public ?string $description = null,
        public ?string $imageUrl = null,
        public ?BillingPeriod $billingPeriod = null,
        public ?TaxMode $taxMode = null,
        public ?TaxCategory $taxCategory = null,
        public ?string $defaultSuccessUrl = null,
        array $customFields = [],
        public ?bool $abandonedCartRecoveryEnabled = null,
    ) {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('The product name cannot be blank.');
        }

        if ($price <= 0) {
            throw new InvalidArgumentException('The product price must be greater than zero.');
        }

        if (! array_is_list($customFields)) {
            throw new InvalidArgumentException('The product custom fields must be a list.');
        }

        foreach ($customFields as $index => $customField) {
            if (! $customField instanceof CustomFieldInput) {
                throw new InvalidArgumentException(sprintf(
                    'Product custom field at index %d must be an instance of %s, %s given.',
                    $index,
                    CustomFieldInput::class,
                    get_debug_type($customField),
                ));
            }
        }

        /** @var list<CustomFieldInput> $customFields */
        $this->name = $name;
        $this->price = $price;
        $this->customFields = $customFields;
    }

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
