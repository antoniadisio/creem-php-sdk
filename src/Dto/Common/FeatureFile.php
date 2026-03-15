<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Common;

use Antoniadisio\Creem\Internal\Hydration\Payload;

final readonly class FeatureFile
{
    public function __construct(
        public ?string $id,
        public ?string $fileName,
        public ?string $url,
        public ?string $type,
        public ?int $size,
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
