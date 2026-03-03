<?php

declare(strict_types=1);

namespace Creem\Dto\Subscription;

use Creem\Dto\Common\ExpandableValue;
use Creem\Dto\Common\StructuredList;
use Creem\Dto\Common\StructuredObject;
use Creem\Internal\Hydration\Payload;

final class Subscription
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $mode,
        public readonly ?string $object,
        public readonly ?ExpandableValue $product,
        public readonly ?ExpandableValue $customer,
        public readonly StructuredList $items,
        public readonly ?string $collectionMethod,
        public readonly ?string $status,
        public readonly ?string $lastTransactionId,
        public readonly ?StructuredObject $lastTransaction,
        public readonly ?string $lastTransactionDate,
        public readonly ?string $nextTransactionDate,
        public readonly ?string $currentPeriodStartDate,
        public readonly ?string $currentPeriodEndDate,
        public readonly ?string $canceledAt,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly ?StructuredObject $discount,
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
            Payload::expandable($payload, 'product'),
            Payload::expandable($payload, 'customer'),
            Payload::list($payload, 'items'),
            Payload::string($payload, 'collection_method'),
            Payload::string($payload, 'status'),
            Payload::string($payload, 'last_transaction_id'),
            Payload::object($payload, 'last_transaction'),
            Payload::string($payload, 'last_transaction_date'),
            Payload::string($payload, 'next_transaction_date'),
            Payload::string($payload, 'current_period_start_date'),
            Payload::string($payload, 'current_period_end_date'),
            Payload::string($payload, 'canceled_at'),
            Payload::string($payload, 'created_at'),
            Payload::string($payload, 'updated_at'),
            Payload::object($payload, 'discount'),
        );
    }
}
