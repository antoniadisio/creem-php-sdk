<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\License;

use Antoniadisio\Creem\Enum\ApiMode;
use Antoniadisio\Creem\Enum\LicenseStatus;
use Antoniadisio\Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

final readonly class License
{
    public function __construct(
        public ?string $id,
        public ?ApiMode $mode,
        public ?string $object,
        public ?LicenseStatus $status,
        public ?string $key,
        public ?int $activation,
        public ?int $activationLimit,
        public ?DateTimeImmutable $expiresAt,
        public ?DateTimeImmutable $createdAt,
        public ?LicenseInstance $instance,
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
