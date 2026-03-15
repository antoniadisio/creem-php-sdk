<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Common;

use Antoniadisio\Creem\Enum\CustomFieldType;
use Antoniadisio\Creem\Internal\Hydration\Payload;

final readonly class CustomField
{
    public function __construct(
        public ?CustomFieldType $type,
        public ?string $key,
        public ?string $label,
        public ?bool $optional,
        public ?CustomFieldText $text,
        public ?CustomFieldCheckbox $checkbox,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            Payload::enum($payload, 'type', self::class, CustomFieldType::class, true),
            Payload::string($payload, 'key', self::class, true),
            Payload::string($payload, 'label', self::class, true),
            Payload::bool($payload, 'optional', self::class),
            Payload::typedObject(
                $payload,
                'text',
                self::class,
                static fn (array $value): CustomFieldText => CustomFieldText::fromPayload($value),
            ),
            Payload::typedObject(
                $payload,
                'checkbox',
                self::class,
                static fn (array $value): CustomFieldCheckbox => CustomFieldCheckbox::fromPayload($value),
            ),
        );
    }
}
