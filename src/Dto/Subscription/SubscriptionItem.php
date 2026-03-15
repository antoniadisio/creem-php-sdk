<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Subscription;

use Antoniadisio\Creem\Enum\ApiMode;
use Antoniadisio\Creem\Internal\Hydration\Payload;

final readonly class SubscriptionItem
{
    public function __construct(
        public ?string $id,
        public ?ApiMode $mode,
        public ?string $object,
        public ?string $productId,
        public ?string $priceId,
        public ?int $units,
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
            Payload::string($payload, 'product_id', self::class),
            Payload::string($payload, 'price_id', self::class),
            Payload::integer($payload, 'units', self::class),
        );
    }
}
