<?php

declare(strict_types=1);

namespace Creem\Dto\License;

use Creem\Enum\ApiMode;
use Creem\Enum\LicenseStatus;
use Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

final class License
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?ApiMode $mode,
        public readonly ?string $object,
        public readonly ?LicenseStatus $status,
        public readonly ?string $key,
        public readonly ?int $activation,
        public readonly ?int $activationLimit,
        public readonly ?DateTimeImmutable $expiresAt,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?LicenseInstance $instance,
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
            Payload::enum($payload, 'status', self::class, LicenseStatus::class, true),
            Payload::string($payload, 'key', self::class, true),
            Payload::integer($payload, 'activation', self::class, true),
            Payload::integer($payload, 'activation_limit', self::class),
            Payload::dateTime($payload, 'expires_at', self::class),
            Payload::dateTime($payload, 'created_at', self::class, true),
            Payload::typedObject(
                $payload,
                'instance',
                self::class,
                static fn (array $value): LicenseInstance => LicenseInstance::fromPayload($value),
            ),
        );
    }
}
