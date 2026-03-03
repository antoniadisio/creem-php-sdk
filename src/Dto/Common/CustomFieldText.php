<?php

declare(strict_types=1);

namespace Creem\Dto\Common;

use Creem\Internal\Hydration\Payload;

final class CustomFieldText
{
    public function __construct(
        public readonly ?int $maxLength,
        public readonly ?int $minimumLength,
        public readonly ?string $value,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            Payload::integer($payload, 'max_length', self::class),
            Payload::integer($payload, 'minimum_length', self::class),
            Payload::string($payload, 'value', self::class),
        );
    }
}
