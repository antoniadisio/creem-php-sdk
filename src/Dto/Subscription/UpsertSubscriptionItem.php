<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Subscription;

use Antoniadisio\Creem\Internal\Serialization\RequestValueNormalizer;
use InvalidArgumentException;

use function trim;

final readonly class UpsertSubscriptionItem
{
    public ?string $id;

    public ?string $productId;

    public ?string $priceId;

    public ?int $units;

    public function __construct(
        ?string $id = null,
        ?string $productId = null,
        ?string $priceId = null,
        ?int $units = null,
    ) {
        $id = $this->normalizeOptionalString($id, 'The subscription item ID cannot be blank.');
        $productId = $this->normalizeOptionalString($productId, 'The subscription item product ID cannot be blank.');
        $priceId = $this->normalizeOptionalString($priceId, 'The subscription item price ID cannot be blank.');

        if ($id === null && $productId === null && $priceId === null) {
            throw new InvalidArgumentException(
                'At least one of subscription item ID, product ID, or price ID must be provided.'
            );
        }

        if ($units !== null && $units <= 0) {
            throw new InvalidArgumentException('The subscription item units must be greater than zero.');
        }

        $this->id = $id;
        $this->productId = $productId;
        $this->priceId = $priceId;
        $this->units = $units;
    }

    /**
     * @return array<string, string|int>
     */
    public function toArray(): array
    {
        /** @var array<string, string|int> */
        return RequestValueNormalizer::payload([
            'id' => $this->id,
            'product_id' => $this->productId,
            'price_id' => $this->priceId,
            'units' => $this->units,
        ]);
    }

    private function normalizeOptionalString(?string $value, string $blankMessage): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException($blankMessage);
        }

        return $value;
    }
}
