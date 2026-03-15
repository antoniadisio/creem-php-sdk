<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Common;

use Antoniadisio\Creem\Internal\Hydration\Payload;

final readonly class CustomFieldCheckbox
{
    public function __construct(
        public ?string $label,
        public ?bool $value,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            Payload::string($payload, 'label', self::class),
            Payload::bool($payload, 'value', self::class),
        );
    }
}
