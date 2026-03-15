<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Common;

use Antoniadisio\Creem\Dto\License\License;
use Antoniadisio\Creem\Enum\ProductFeatureType;
use Antoniadisio\Creem\Internal\Hydration\Payload;

final readonly class ProductFeature
{
    public function __construct(
        public ?string $id,
        public ?string $description,
        public ?ProductFeatureType $type,
        public ?string $privateNote,
        public ?FileFeature $file,
        public ?License $licenseKey,
        public ?License $license,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            Payload::string($payload, 'id', self::class),
            Payload::string($payload, 'description', self::class),
            Payload::enum($payload, 'type', self::class, ProductFeatureType::class),
            Payload::string($payload, 'private_note', self::class),
            Payload::typedObject(
                $payload,
                'file',
                self::class,
                static fn (array $value): FileFeature => FileFeature::fromPayload($value),
            ),
            Payload::typedObject(
                $payload,
                'license_key',
                self::class,
                static fn (array $value): License => License::fromPayload($value),
            ),
            Payload::typedObject(
                $payload,
                'license',
                self::class,
                static fn (array $value): License => License::fromPayload($value),
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromCatalogPayload(array $payload): self
    {
        return new self(
            Payload::string($payload, 'id', self::class, true),
            Payload::string($payload, 'description', self::class, true),
            Payload::enum($payload, 'type', self::class, ProductFeatureType::class, true),
            null,
            null,
            null,
            null,
        );
    }
}
