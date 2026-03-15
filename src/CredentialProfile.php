<?php

declare(strict_types=1);

namespace Antoniadisio\Creem;

use Antoniadisio\Creem\Enum\Environment;
use LogicException;

use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;

final readonly class CredentialProfile implements \Stringable
{
    private Config $config;

    private ?string $webhookSecret;

    public function __construct(
        #[\SensitiveParameter]
        string $apiKey,
        Environment $environment = Environment::Production,
        ?string $baseUrl = null,
        int|float|null $timeout = null,
        ?string $userAgentSuffix = null,
        bool $allowUnsafeBaseUrlOverride = false,
        #[\SensitiveParameter]
        ?string $webhookSecret = null,
    ) {
        $this->config = new Config(
            apiKey: $apiKey,
            environment: $environment,
            baseUrl: $baseUrl,
            timeout: $timeout,
            userAgentSuffix: $userAgentSuffix,
            allowUnsafeBaseUrlOverride: $allowUnsafeBaseUrlOverride,
        );
        $this->webhookSecret = $this->normalizeWebhookSecret($webhookSecret);
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function apiKey(): string
    {
        return $this->config->apiKey();
    }

    public function environment(): Environment
    {
        return $this->config->environment();
    }

    public function baseUrl(): ?string
    {
        return $this->config->baseUrl();
    }

    public function timeout(): ?float
    {
        return $this->config->timeout();
    }

    public function userAgentSuffix(): ?string
    {
        return $this->config->userAgentSuffix();
    }

    public function webhookSecret(): ?string
    {
        return $this->webhookSecret;
    }

    public function hasWebhookSecret(): bool
    {
        return $this->webhookSecret !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return $this->safeRepresentation();
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return $this->safeRepresentation();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        throw new LogicException('Unserializing Creem\\CredentialProfile is not supported.');
    }

    public function __toString(): string
    {
        return sprintf(
            'Creem\\CredentialProfile(config=%s, webhookSecret=%s)',
            (string) $this->config,
            $this->redactedWebhookSecret() ?? 'null',
        );
    }

    private function normalizeWebhookSecret(#[\SensitiveParameter] ?string $webhookSecret): ?string
    {
        if ($webhookSecret === null) {
            return null;
        }

        $webhookSecret = trim($webhookSecret);

        return $webhookSecret === '' ? null : $webhookSecret;
    }

    /**
     * @return array<string, mixed>
     */
    private function safeRepresentation(): array
    {
        return [
            'config' => $this->config->__debugInfo(),
            'webhookSecret' => $this->redactedWebhookSecret(),
        ];
    }

    private function redactedWebhookSecret(): ?string
    {
        if ($this->webhookSecret === null) {
            return null;
        }

        $visibleSuffix = strlen($this->webhookSecret) > 8 ? substr($this->webhookSecret, -4) : '';

        if (str_starts_with($this->webhookSecret, 'whsec_')) {
            return 'whsec_****'.$visibleSuffix;
        }

        return '****'.$visibleSuffix;
    }
}
