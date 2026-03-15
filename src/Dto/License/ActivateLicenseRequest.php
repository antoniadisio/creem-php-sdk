<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\License;

use Antoniadisio\Creem\Internal\Serialization\RequestValueNormalizer;
use InvalidArgumentException;

use function trim;

final readonly class ActivateLicenseRequest
{
    public string $key;

    public string $instanceName;

    public function __construct(
        string $key,
        string $instanceName,
    ) {
        $key = trim($key);

        if ($key === '') {
            throw new InvalidArgumentException('The license key cannot be blank.');
        }

        $instanceName = trim($instanceName);

        if ($instanceName === '') {
            throw new InvalidArgumentException('The license instance name cannot be blank.');
        }

        $this->key = $key;
        $this->instanceName = $instanceName;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        /** @var array<string, string> */
        return RequestValueNormalizer::payload([
            'key' => $this->key,
            'instance_name' => $this->instanceName,
        ]);
    }
}
