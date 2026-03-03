<?php

declare(strict_types=1);

namespace Creem\Dto\License;

use Creem\Dto\Common\StructuredObject;
use Creem\Internal\Hydration\Payload;

final class License
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $mode,
        public readonly ?string $object,
        public readonly ?string $status,
        public readonly ?string $key,
        public readonly int|float|null $activation,
        public readonly int|float|null $activationLimit,
        public readonly ?string $expiresAt,
        public readonly ?string $createdAt,
        public readonly ?StructuredObject $instance,
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
            Payload::string($payload, 'key'),
            Payload::number($payload, 'activation'),
            Payload::number($payload, 'activation_limit'),
            Payload::string($payload, 'expires_at'),
            Payload::string($payload, 'created_at'),
            Payload::object($payload, 'instance'),
        );
    }
}
