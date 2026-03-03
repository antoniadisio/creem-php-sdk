<?php

declare(strict_types=1);

namespace Creem;

use Creem\Internal\Http\UserAgent;
use InvalidArgumentException;

final readonly class Config
{
    private string $apiKey;

    private ?string $baseUrl;

    private ?float $timeout;

    private ?string $userAgentSuffix;

    public function __construct(
        string $apiKey,
        private Environment $environment = Environment::Production,
        ?string $baseUrl = null,
        int|float|null $timeout = null,
        ?string $userAgentSuffix = null,
    ) {
        $apiKey = trim($apiKey);

        if ($apiKey === '') {
            throw new InvalidArgumentException('The Creem API key cannot be empty.');
        }

        if ($timeout !== null && $timeout <= 0) {
            throw new InvalidArgumentException('The Creem request timeout must be greater than zero.');
        }

        $normalizedBaseUrl = $baseUrl === null ? null : rtrim(trim($baseUrl), '/');

        if ($normalizedBaseUrl === '') {
            throw new InvalidArgumentException('The Creem base URL override cannot be blank.');
        }

        $normalizedSuffix = $userAgentSuffix === null ? null : trim($userAgentSuffix);

        if ($normalizedSuffix === '') {
            $normalizedSuffix = null;
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = $normalizedBaseUrl;
        $this->timeout = $timeout === null ? null : (float) $timeout;
        $this->userAgentSuffix = $normalizedSuffix;
    }

    public function apiKey(): string
    {
        return $this->apiKey;
    }

    public function environment(): Environment
    {
        return $this->environment;
    }

    public function baseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function timeout(): ?float
    {
        return $this->timeout;
    }

    public function userAgentSuffix(): ?string
    {
        return $this->userAgentSuffix;
    }

    public function resolveBaseUrl(): string
    {
        return $this->baseUrl ?? $this->environment->baseUrl();
    }

    public function userAgent(): string
    {
        return UserAgent::forConfig($this);
    }
}
