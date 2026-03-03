<?php

declare(strict_types=1);

namespace Creem\Dto\Common;

use Creem\Enum\CustomFieldType;
use Creem\Internal\Hydration\Payload;

final class CustomField
{
    public function __construct(
        public readonly ?CustomFieldType $type,
        public readonly ?string $key,
        public readonly ?string $label,
        public readonly ?bool $optional,
        public readonly ?CustomFieldText $text,
        public readonly ?CustomFieldCheckbox $checkbox,
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
