<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Common;

use Antoniadisio\Creem\Internal\Serialization\RequestValueNormalizer;

final readonly class CheckboxFieldConfigInput
{
    public function __construct(
        public ?string $label = null,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        /** @var array<string, string> */
        return RequestValueNormalizer::payload([
            'label' => $this->label,
        ]);
    }
}
