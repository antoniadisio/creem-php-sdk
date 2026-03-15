<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Checkout;

use Antoniadisio\Creem\Dto\Common\CustomFieldInput;
use Antoniadisio\Creem\Internal\Serialization\RequestValueNormalizer;
use InvalidArgumentException;

use function array_is_list;
use function get_debug_type;
use function sprintf;
use function trim;

final readonly class CreateCheckoutRequest
{
    public string $productId;

    public ?string $requestId;

    public ?int $units;

    public ?string $discountCode;

    /**
     * @var list<CustomFieldInput>
     */
    public array $customFields;

    /**
     * @param  array<array-key, mixed>  $customFields
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        string $productId,
        ?string $requestId = null,
        ?int $units = null,
        ?string $discountCode = null,
        public ?CheckoutCustomerInput $customer = null,
        array $customFields = [],
        public ?string $successUrl = null,
        public ?array $metadata = null,
    ) {
        $productId = trim($productId);

        if ($productId === '') {
            throw new InvalidArgumentException('The checkout product ID cannot be blank.');
        }

        if ($requestId !== null) {
            $requestId = trim($requestId);

            if ($requestId === '') {
                throw new InvalidArgumentException('The checkout request ID cannot be blank.');
            }
        }

        if ($discountCode !== null) {
            $discountCode = trim($discountCode);

            if ($discountCode === '') {
                throw new InvalidArgumentException('The checkout discount code cannot be blank.');
            }
        }

        if ($units !== null && $units <= 0) {
            throw new InvalidArgumentException('The checkout units must be greater than zero.');
        }

        if (! array_is_list($customFields)) {
            throw new InvalidArgumentException('The checkout custom fields must be a list.');
        }

        foreach ($customFields as $index => $customField) {
            if (! $customField instanceof CustomFieldInput) {
                throw new InvalidArgumentException(sprintf(
                    'Checkout custom field at index %d must be an instance of %s, %s given.',
                    $index,
                    CustomFieldInput::class,
                    get_debug_type($customField),
                ));
            }
        }

        /** @var list<CustomFieldInput> $customFields */
        $this->productId = $productId;
        $this->requestId = $requestId;
        $this->units = $units;
        $this->discountCode = $discountCode;
        $this->customFields = $customFields;
    }

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
