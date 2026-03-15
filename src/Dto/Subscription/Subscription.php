<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Subscription;

use Antoniadisio\Creem\Dto\Common\ExpandableResource;
use Antoniadisio\Creem\Dto\Customer\Customer;
use Antoniadisio\Creem\Dto\Product\Product;
use Antoniadisio\Creem\Dto\Transaction\Transaction;
use Antoniadisio\Creem\Enum\ApiMode;
use Antoniadisio\Creem\Enum\SubscriptionCollectionMethod;
use Antoniadisio\Creem\Enum\SubscriptionStatus;
use Antoniadisio\Creem\Exception\HydrationException;
use Antoniadisio\Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

use function array_is_list;
use function is_array;

final readonly class Subscription
{
    /**
     * @param  ExpandableResource<Product>|null  $product
     * @param  ExpandableResource<Customer>|null  $customer
     * @param  list<SubscriptionItem>  $items
     * @param  array<string, mixed>|null  $discount
     */
    public function __construct(
        public ?string $id,
        public ?ApiMode $mode,
        public ?string $object,
        public ?ExpandableResource $product,
        public ?ExpandableResource $customer,
        public array $items,
        public ?SubscriptionCollectionMethod $collectionMethod,
        public ?SubscriptionStatus $status,
        public ?string $lastTransactionId,
        public ?Transaction $lastTransaction,
        public ?DateTimeImmutable $lastTransactionDate,
        public ?DateTimeImmutable $nextTransactionDate,
        public ?DateTimeImmutable $currentPeriodStartDate,
        public ?DateTimeImmutable $currentPeriodEndDate,
        public ?DateTimeImmutable $canceledAt,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
        public ?array $discount,
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
            Payload::expandableResource(
                $payload,
                'product',
                self::class,
                static fn (array $value): Product => Product::fromPayload($value),
                true,
            ),
            Payload::expandableResource(
                $payload,
                'customer',
                self::class,
                static fn (array $value): Customer => Customer::fromPayload($value),
                true,
            ),
            Payload::typedList(
                $payload,
                'items',
                self::class,
                static function (mixed $item): SubscriptionItem {
                    if (! is_array($item) || array_is_list($item)) {
                        throw HydrationException::invalidField(self::class, 'items', 'object', $item);
                    }

                    /** @var array<string, mixed> $item */
                    return SubscriptionItem::fromPayload($item);
                },
            ),
            Payload::enum($payload, 'collection_method', self::class, SubscriptionCollectionMethod::class, true),
            Payload::enum($payload, 'status', self::class, SubscriptionStatus::class, true),
            Payload::string($payload, 'last_transaction_id', self::class),
            Payload::typedObject(
                $payload,
                'last_transaction',
                self::class,
                static fn (array $value): Transaction => Transaction::fromPayload($value),
            ),
            Payload::dateTime($payload, 'last_transaction_date', self::class),
            Payload::dateTime($payload, 'next_transaction_date', self::class),
            Payload::dateTime($payload, 'current_period_start_date', self::class),
            Payload::dateTime($payload, 'current_period_end_date', self::class),
            Payload::dateTime($payload, 'canceled_at', self::class),
            Payload::dateTime($payload, 'created_at', self::class, true),
            Payload::dateTime($payload, 'updated_at', self::class, true),
            Payload::arrayObject($payload, 'discount', self::class),
        );
    }
}
