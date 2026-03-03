<?php

declare(strict_types=1);

namespace Creem\Dto\Checkout;

use Creem\Dto\Common\CustomField;
use Creem\Dto\Common\ExpandableResource;
use Creem\Dto\Common\Order;
use Creem\Dto\Common\ProductFeature;
use Creem\Dto\Customer\Customer;
use Creem\Dto\Product\Product;
use Creem\Dto\Subscription\Subscription;
use Creem\Enum\ApiMode;
use Creem\Enum\CheckoutStatus;
use Creem\Exception\HydrationException;
use Creem\Internal\Hydration\Payload;

use function array_is_list;
use function is_array;

final class Checkout
{
    /**
     * @param  ExpandableResource<Product>|null  $product
     * @param  ExpandableResource<Subscription>|null  $subscription
     * @param  ExpandableResource<Customer>|null  $customer
     * @param  list<CustomField>  $customFields
     * @param  list<ProductFeature>  $feature
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?ApiMode $mode,
        public readonly ?string $object,
        public readonly ?CheckoutStatus $status,
        public readonly ?string $requestId,
        public readonly ?ExpandableResource $product,
        public readonly ?int $units,
        public readonly ?Order $order,
        public readonly ?ExpandableResource $subscription,
        public readonly ?ExpandableResource $customer,
        public readonly array $customFields,
        public readonly ?string $checkoutUrl,
        public readonly ?string $successUrl,
        public readonly array $feature,
        public readonly ?array $metadata,
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
            Payload::enum($payload, 'status', self::class, CheckoutStatus::class, true),
            Payload::string($payload, 'request_id', self::class),
            Payload::expandableResource(
                $payload,
                'product',
                self::class,
                static fn (array $value): Product => Product::fromPayload($value),
                true,
            ),
            Payload::integer($payload, 'units', self::class),
            Payload::typedObject(
                $payload,
                'order',
                self::class,
                static fn (array $value): Order => Order::fromPayload($value),
            ),
            Payload::expandableResource(
                $payload,
                'subscription',
                self::class,
                static fn (array $value): Subscription => Subscription::fromPayload($value),
            ),
            Payload::expandableResource(
                $payload,
                'customer',
                self::class,
                static fn (array $value): Customer => Customer::fromPayload($value),
            ),
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
            Payload::string($payload, 'checkout_url', self::class),
            Payload::string($payload, 'success_url', self::class),
            Payload::typedList(
                $payload,
                'feature',
                self::class,
                static function (mixed $item): ProductFeature {
                    if (! is_array($item) || array_is_list($item)) {
                        throw HydrationException::invalidField(self::class, 'feature', 'object', $item);
                    }

                    /** @var array<string, mixed> $item */
                    return ProductFeature::fromPayload($item);
                },
            ),
            Payload::arrayObject($payload, 'metadata', self::class),
        );
    }
}
