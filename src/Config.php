<?php

declare(strict_types=1);

namespace Antoniadisio\Creem;

use Antoniadisio\Creem\Enum\Environment;
use Antoniadisio\Creem\Internal\Http\UserAgent;
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

use const FILTER_VALIDATE_URL;
use const PHP_URL_HOST;
use const PHP_URL_SCHEME;

final readonly class Config implements \Stringable
{
    public const float DEFAULT_TIMEOUT_SECONDS = 30.0;

    private const string API_KEY_PATTERN = '/^(?:creem_test|creem)_[A-Za-z0-9][A-Za-z0-9._-]*$/';
    private const string CREEM_TEST_API_KEY_PREFIX = 'creem_test_';
    private const string CREEM_PRODUCTION_API_KEY_PREFIX = 'creem_';

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
        $this->apiKey = $this->normalizeApiKey($apiKey);
        $this->baseUrl = $this->normalizeBaseUrl($baseUrl, $allowUnsafeBaseUrlOverride);
        $this->timeout = $this->normalizeTimeout($timeout);
        $this->userAgentSuffix = $this->normalizeUserAgentSuffix($userAgentSuffix);
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

        return 'creem_****' . $visibleSuffix;
    }

    private function normalizeApiKey(#[\SensitiveParameter] string $apiKey): string
    {
        $apiKey = trim($apiKey);

        if ($apiKey === '') {
            throw new InvalidArgumentException('The Creem API key cannot be empty.');
        }

        if (! preg_match(self::API_KEY_PATTERN, $apiKey)) {
            throw new InvalidArgumentException('The Creem API key must start with "creem_test_" or "creem_".');
        }

        $this->assertApiKeyMatchesEnvironment($apiKey);

        return $apiKey;
    }

    private function assertApiKeyMatchesEnvironment(#[\SensitiveParameter] string $apiKey): void
    {
        if (str_starts_with($apiKey, self::CREEM_TEST_API_KEY_PREFIX) && $this->environment !== Environment::Test) {
            throw new InvalidArgumentException(
                'Creem test API keys starting with "creem_test_" require Environment::Test.',
            );
        }

        if (
            str_starts_with($apiKey, self::CREEM_PRODUCTION_API_KEY_PREFIX)
            && ! str_starts_with($apiKey, self::CREEM_TEST_API_KEY_PREFIX)
            && $this->environment !== Environment::Production
        ) {
            throw new InvalidArgumentException(
                'Creem production API keys starting with "creem_" require Environment::Production.',
            );
        }
    }

    private function normalizeBaseUrl(?string $baseUrl, bool $allowUnsafeBaseUrlOverride): ?string
    {
        if ($baseUrl === null) {
            return null;
        }

        $normalizedBaseUrl = rtrim(trim($baseUrl), '/');

        if ($normalizedBaseUrl === '') {
            throw new InvalidArgumentException('The Creem base URL override cannot be blank.');
        }

        $baseUrlScheme = parse_url($normalizedBaseUrl, PHP_URL_SCHEME);
        $baseUrlHost = parse_url($normalizedBaseUrl, PHP_URL_HOST);

        if (
            filter_var($normalizedBaseUrl, FILTER_VALIDATE_URL) === false
            || ! is_string($baseUrlScheme)
            || ! is_string($baseUrlHost)
            || strtolower($baseUrlScheme) !== 'https'
        ) {
            throw new InvalidArgumentException('The Creem base URL override must be a valid HTTPS URL.');
        }

        if (! $allowUnsafeBaseUrlOverride && ! $this->isTrustedBaseUrlHost($baseUrlHost)) {
            throw new InvalidArgumentException(
                'The Creem base URL override host is not trusted. Pass allowUnsafeBaseUrlOverride: true to allow non-Creem hosts.',
            );
        }

        return $normalizedBaseUrl;
    }

    private function normalizeTimeout(int|float|null $timeout): ?float
    {
        if ($timeout === null) {
            return null;
        }

        if ($timeout <= 0) {
            throw new InvalidArgumentException('The Creem request timeout must be greater than zero.');
        }

        return (float) $timeout;
    }

    private function normalizeUserAgentSuffix(?string $userAgentSuffix): ?string
    {
        if ($userAgentSuffix === null) {
            return null;
        }

        $normalizedSuffix = preg_replace('/[\x00-\x1F\x7F]+/', '', $userAgentSuffix);
        $normalizedSuffix = is_string($normalizedSuffix) ? trim($normalizedSuffix) : '';

        return $normalizedSuffix === '' ? null : $normalizedSuffix;
    }

    private function isTrustedBaseUrlHost(string $host): bool
    {
        return in_array(strtolower($host), self::TRUSTED_BASE_URL_HOSTS, true);
    }
}
