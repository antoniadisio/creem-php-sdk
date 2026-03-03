<?php

declare(strict_types=1);

namespace Creem\Dto\Common;

use Creem\Internal\Hydration\StructuredValueNormalizer;

use function array_key_exists;

final class StructuredObject
{
    /**
     * @param  array<string, mixed>  $values
     */
    private function __construct(
        private readonly array $values,
    ) {}

    /**
     * @param  array<string, mixed>  $values
     */
    public static function fromArray(array $values): self
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            $normalized[$key] = StructuredValueNormalizer::normalize($value);
        }

        /** @var array<string, mixed> $normalized */
        return new self($normalized);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->values;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }
}
