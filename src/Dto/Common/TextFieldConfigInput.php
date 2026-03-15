<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Common;

use Antoniadisio\Creem\Internal\Serialization\RequestValueNormalizer;

final readonly class TextFieldConfigInput
{
    public function __construct(
        public ?int $maxLength = null,
        public ?int $minLength = null,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        /** @var array<string, int> */
        return RequestValueNormalizer::payload([
            'max_length' => $this->maxLength,
            'min_length' => $this->minLength,
        ]);
    }
}
