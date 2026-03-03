<?php

declare(strict_types=1);

namespace Creem\Dto\Customer;

use Creem\Internal\Hydration\Payload;

final class Customer
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $mode,
        public readonly ?string $object,
        public readonly ?string $email,
        public readonly ?string $name,
        public readonly ?string $country,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
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
            Payload::string($payload, 'email'),
            Payload::string($payload, 'name'),
            Payload::string($payload, 'country'),
            Payload::string($payload, 'created_at'),
            Payload::string($payload, 'updated_at'),
        );
    }
}
