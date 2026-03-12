<?php

declare(strict_types=1);

namespace Creem;

use const FILTER_VALIDATE_URL;
use const PHP_URL_HOST;
use const PHP_URL_SCHEME;

use Creem\Enum\Environment;
use Creem\Internal\Http\UserAgent;
use InvalidArgumentException;
use LogicException;

use function filter_var;
use function is_string;
use function parse_url;
use function preg_match;
use function preg_replace;
use function rtrim;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
use function trim;

final readonly class Config implements \Stringable
{
    public const float DEFAULT_TIMEOUT_SECONDS = 30.0;

    private const string API_KEY_PATTERN = '/^(?:sk|creem)_[A-Za-z0-9][A-Za-z0-9._-]*$/';

    /**
     * @var array<int, string>
     */
    private const array TRUSTED_BASE_URL_HOSTS = [
        'api.creem.io',
        'test-api.creem.io',
    ];

    private string $apiKey;

    private ?string $baseUrl;

    private ?float $timeout;

    private ?string $userAgentSuffix;

    public function __construct(
        #[\SensitiveParameter]
        string $apiKey,
        private Environment $environment = Environment::Production,
        ?string $baseUrl = null,
        int|float|null $timeout = null,
        ?string $userAgentSuffix = null,
        bool $allowUnsafeBaseUrlOverride = false,
    ) {
        $apiKey = trim($apiKey);

        if ($apiKey === '') {
            throw new InvalidArgumentException('The Creem API key cannot be empty.');
        }

        if (! preg_match(self::API_KEY_PATTERN, $apiKey)) {
            throw new InvalidArgumentException('The Creem API key must start with "sk_" or "creem_".');
        }

        if ($timeout !== null && $timeout <= 0) {
            throw new InvalidArgumentException('The Creem request timeout must be greater than zero.');
        }

        $normalizedBaseUrl = $baseUrl === null ? null : rtrim(trim($baseUrl), '/');

        if ($normalizedBaseUrl === '') {
            throw new InvalidArgumentException('The Creem base URL override cannot be blank.');
        }

        $baseUrlScheme = $normalizedBaseUrl === null ? null : parse_url($normalizedBaseUrl, PHP_URL_SCHEME);
        $baseUrlHost = $normalizedBaseUrl === null ? null : parse_url($normalizedBaseUrl, PHP_URL_HOST);

        if (
            $normalizedBaseUrl !== null
            && (
                filter_var($normalizedBaseUrl, FILTER_VALIDATE_URL) === false
                || ! is_string($baseUrlScheme)
                || ! is_string($baseUrlHost)
                || strtolower($baseUrlScheme) !== 'https'
            )
        ) {
            throw new InvalidArgumentException('The Creem base URL override must be a valid HTTPS URL.');
        }

        if (
            $normalizedBaseUrl !== null
            && ! $this->isTrustedBaseUrlHost($baseUrlHost)
            && ! $allowUnsafeBaseUrlOverride
        ) {
            throw new InvalidArgumentException(
                'The Creem base URL override host is not trusted. Pass allowUnsafeBaseUrlOverride: true to allow non-Creem hosts.',
            );
        }

        $normalizedSuffix = $userAgentSuffix === null
            ? null
            : trim((string) (preg_replace('/[\x00-\x1F\x7F]+/', '', $userAgentSuffix) ?? ''));

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
        throw new LogicException('Unserializing Creem\\Config is not supported.');
    }

    public function __toString(): string
    {
        return sprintf(
            'Creem\\Config(apiKey=%s, environment=%s, baseUrl=%s, timeout=%s, userAgentSuffix=%s)',
            $this->redactedApiKey(),
            $this->environment->value,
            $this->baseUrl ?? 'null',
            (string) $this->effectiveTimeout(),
            $this->userAgentSuffix ?? 'null',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function safeRepresentation(): array
    {
        return [
            'apiKey' => $this->redactedApiKey(),
            'environment' => $this->environment->value,
            'baseUrl' => $this->baseUrl,
            'configuredTimeout' => $this->timeout,
            'effectiveTimeout' => $this->effectiveTimeout(),
            'userAgentSuffix' => $this->userAgentSuffix,
        ];
    }

    private function effectiveTimeout(): float
    {
        return $this->timeout ?? self::DEFAULT_TIMEOUT_SECONDS;
    }

    private function redactedApiKey(): string
    {
        $visibleSuffix = strlen($this->apiKey) > 7 ? substr($this->apiKey, -4) : '';

        if (str_starts_with($this->apiKey, 'creem_')) {
            return 'creem_****'.$visibleSuffix;
        }

        return 'sk_****'.$visibleSuffix;
    }

    private function isTrustedBaseUrlHost(string $host): bool
    {
        return in_array(strtolower($host), self::TRUSTED_BASE_URL_HOSTS, true);
    }
}
