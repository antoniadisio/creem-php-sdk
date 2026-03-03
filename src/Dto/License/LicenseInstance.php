<?php

declare(strict_types=1);

namespace Creem\Dto\License;

use Creem\Enum\ApiMode;
use Creem\Enum\LicenseInstanceStatus;
use Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

final class LicenseInstance
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?ApiMode $mode,
        public readonly ?string $object,
        public readonly ?string $name,
        public readonly ?LicenseInstanceStatus $status,
        public readonly ?DateTimeImmutable $createdAt,
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
            Payload::string($payload, 'name', self::class, true),
            Payload::enum($payload, 'status', self::class, LicenseInstanceStatus::class, true),
            Payload::dateTime($payload, 'created_at', self::class, true),
        );
    }
}
