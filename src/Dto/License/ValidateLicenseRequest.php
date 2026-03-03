<?php

declare(strict_types=1);

namespace Creem\Dto\License;

final class ValidateLicenseRequest
{
    public function __construct(
        public readonly string $key,
        public readonly string $instanceId,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'instance_id' => $this->instanceId,
        ];
    }
}
