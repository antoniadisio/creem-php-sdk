<?php

declare(strict_types=1);

namespace Creem\Dto\Common;

use Creem\Internal\Hydration\Payload;

final class FeatureFile
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $fileName,
        public readonly ?string $url,
        public readonly ?string $type,
        public readonly ?int $size,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            Payload::string($payload, 'id', self::class, true),
            Payload::string($payload, 'file_name', self::class, true),
            Payload::string($payload, 'url', self::class, true),
            Payload::string($payload, 'type', self::class, true),
            Payload::integer($payload, 'size', self::class, true),
        );
    }
}
