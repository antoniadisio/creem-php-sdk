<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Subscription;

use Antoniadisio\Creem\Enum\SubscriptionUpdateBehavior;
use Antoniadisio\Creem\Internal\Serialization\RequestValueNormalizer;
use InvalidArgumentException;

use function array_is_list;
use function get_debug_type;
use function sprintf;

final readonly class UpdateSubscriptionRequest
{
    /**
     * @var list<UpsertSubscriptionItem>
     */
    public array $items;

    /**
     * @param  array<array-key, mixed>  $items
     */
    public function __construct(
        array $items = [],
        public ?SubscriptionUpdateBehavior $updateBehavior = null,
    ) {
        if (! array_is_list($items)) {
            throw new InvalidArgumentException('The subscription items must be a list.');
        }

        foreach ($items as $index => $item) {
            if (! $item instanceof UpsertSubscriptionItem) {
                throw new InvalidArgumentException(sprintf(
                    'Subscription item at index %d must be an instance of %s, %s given.',
                    $index,
                    UpsertSubscriptionItem::class,
                    get_debug_type($item),
                ));
            }
        }

        /** @var list<UpsertSubscriptionItem> $items */
        $this->items = $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return RequestValueNormalizer::payload([
            'items' => $this->items,
            'update_behavior' => $this->updateBehavior,
        ]);
    }
}
