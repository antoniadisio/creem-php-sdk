<?php

declare(strict_types=1);

namespace Creem\Dto\Common;

use Creem\Enum\CustomFieldType;
use Creem\Internal\Serialization\RequestValueNormalizer;

final readonly class CustomFieldInput
{
    public function __construct(
        public CustomFieldType $type,
        public string $key,
        public string $label,
        public ?bool $optional = null,
        public ?TextFieldConfigInput $text = null,
        public ?CheckboxFieldConfigInput $checkbox = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return RequestValueNormalizer::payload([
            'type' => $this->type,
            'key' => $this->key,
            'label' => $this->label,
            'optional' => $this->optional,
            'text' => $this->text,
            'checkbox' => $this->checkbox,
        ]);
    }
}
