<?php

declare(strict_types=1);

namespace Creem\Dto\Subscription;

use Creem\Enum\ApiMode;
use Creem\Internal\Hydration\Payload;

final class SubscriptionItem
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?ApiMode $mode,
        public readonly ?string $object,
        public readonly ?string $productId,
        public readonly ?string $priceId,
        public readonly ?int $units,
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
