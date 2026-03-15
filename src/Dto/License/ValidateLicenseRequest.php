<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\License;

use Antoniadisio\Creem\Internal\Serialization\RequestValueNormalizer;
use InvalidArgumentException;

use function trim;

final readonly class ValidateLicenseRequest
{
    public string $key;

    public string $instanceId;

    public function __construct(
        string $key,
        string $instanceId,
    ) {
        $key = trim($key);

        if ($key === '') {
            throw new InvalidArgumentException('The license key cannot be blank.');
        }

        $instanceId = trim($instanceId);

        if ($instanceId === '') {
            throw new InvalidArgumentException('The license instance ID cannot be blank.');
        }

        $this->key = $key;
        $this->instanceId = $instanceId;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        /** @var array<string, string> */
        return RequestValueNormalizer::payload([
            'key' => $this->key,
            'instance_id' => $this->instanceId,
        ]);
    }
}
