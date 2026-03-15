<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\License;

use Antoniadisio\Creem\Enum\ApiMode;
use Antoniadisio\Creem\Enum\LicenseInstanceStatus;
use Antoniadisio\Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

final readonly class LicenseInstance
{
    public function __construct(
        public ?string $id,
        public ?ApiMode $mode,
        public ?string $object,
        public ?string $name,
        public ?LicenseInstanceStatus $status,
        public ?DateTimeImmutable $createdAt,
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
