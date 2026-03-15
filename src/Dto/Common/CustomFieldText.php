<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Common;

use Antoniadisio\Creem\Internal\Hydration\Payload;

final readonly class CustomFieldText
{
    public function __construct(
        public ?int $maxLength,
        public ?int $minimumLength,
        public ?string $value,
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
