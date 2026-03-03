<?php

declare(strict_types=1);

namespace Creem\Dto\Checkout;

use Creem\Dto\Common\ExpandableValue;
use Creem\Dto\Common\StructuredList;
use Creem\Dto\Common\StructuredObject;
use Creem\Internal\Hydration\Payload;

final class Checkout
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $mode,
        public readonly ?string $object,
        public readonly ?string $status,
        public readonly ?string $requestId,
        public readonly ?ExpandableValue $product,
        public readonly int|float|null $units,
        public readonly ?StructuredObject $order,
        public readonly ?ExpandableValue $subscription,
        public readonly ?ExpandableValue $customer,
        public readonly StructuredList $customFields,
        public readonly ?string $checkoutUrl,
        public readonly ?string $successUrl,
        public readonly StructuredList $feature,
        public readonly ?StructuredObject $metadata,
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
            Payload::string($payload, 'status'),
            Payload::string($payload, 'request_id'),
            Payload::expandable($payload, 'product'),
            Payload::number($payload, 'units'),
            Payload::object($payload, 'order'),
            Payload::expandable($payload, 'subscription'),
            Payload::expandable($payload, 'customer'),
            Payload::list($payload, 'custom_fields'),
            Payload::string($payload, 'checkout_url'),
            Payload::string($payload, 'success_url'),
            Payload::list($payload, 'feature'),
            Payload::object($payload, 'metadata'),
        );
    }
}
