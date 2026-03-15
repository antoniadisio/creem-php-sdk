<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Customer;

use Antoniadisio\Creem\Enum\ApiMode;
use Antoniadisio\Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

final readonly class Customer
{
    public function __construct(
        public ?string $id,
        public ?ApiMode $mode,
        public ?string $object,
        public ?string $email,
        public ?string $name,
        public ?string $country,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
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
            Payload::string($payload, 'email', self::class, true),
            Payload::string($payload, 'name', self::class),
            Payload::string($payload, 'country', self::class, true),
            Payload::dateTime($payload, 'created_at', self::class, true),
            Payload::dateTime($payload, 'updated_at', self::class, true),
        );
    }
}
