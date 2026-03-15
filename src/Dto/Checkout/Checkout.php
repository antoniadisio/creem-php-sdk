<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Checkout;

use Antoniadisio\Creem\Dto\Common\CustomField;
use Antoniadisio\Creem\Dto\Common\ExpandableResource;
use Antoniadisio\Creem\Dto\Common\Order;
use Antoniadisio\Creem\Dto\Common\ProductFeature;
use Antoniadisio\Creem\Dto\Customer\Customer;
use Antoniadisio\Creem\Dto\Product\Product;
use Antoniadisio\Creem\Dto\Subscription\Subscription;
use Antoniadisio\Creem\Enum\ApiMode;
use Antoniadisio\Creem\Enum\CheckoutStatus;
use Antoniadisio\Creem\Exception\HydrationException;
use Antoniadisio\Creem\Internal\Hydration\Payload;

use function array_is_list;
use function array_key_exists;
use function is_array;

final readonly class Checkout
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
        public ?string $id,
        public ?ApiMode $mode,
        public ?string $object,
        public ?CheckoutStatus $status,
        public ?string $requestId,
        public ?ExpandableResource $product,
        public ?int $units,
        public ?Order $order,
        public ?ExpandableResource $subscription,
        public ?ExpandableResource $customer,
        public array $customFields,
        public ?string $checkoutUrl,
        public ?string $successUrl,
        public array $feature,
        public ?array $metadata,
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
            self::features($payload),
            Payload::arrayObject($payload, 'metadata', self::class),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<ProductFeature>
     */
    private static function features(array $payload): array
    {
        if (! array_key_exists('feature', $payload) || $payload['feature'] === null) {
            return [];
        }

        $value = $payload['feature'];

        if (! is_array($value)) {
            throw HydrationException::invalidField(self::class, 'feature', 'list', $value);
        }

        if (! array_is_list($value)) {
            /** @var array<string, mixed> $value */
            return [ProductFeature::fromPayload($value)];
        }

        $features = [];

        foreach ($value as $item) {
            if (! is_array($item) || array_is_list($item)) {
                throw HydrationException::invalidField(self::class, 'feature', 'object', $item);
            }

            /** @var array<string, mixed> $item */
            $features[] = ProductFeature::fromPayload($item);
        }

        return $features;
    }
}
