<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http;

use Antoniadisio\Creem\Config;
use Antoniadisio\Creem\Exception\TransportException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Throwable;

final class CreemConnector extends Connector
{
    use AlwaysThrowOnErrors;

    public function __construct(
        private readonly Config $sdkConfig,
    ) {}

    public function resolveBaseUrl(): string
    {
        return $this->sdkConfig->resolveBaseUrl();
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => $this->sdkConfig->userAgent(),
            'x-api-key' => $this->sdkConfig->apiKey(),
        ];
    }

    /**
     * @return array<string, bool|float|int>
     */
    protected function defaultConfig(): array
    {
        $timeout = $this->sdkConfig->timeout() ?? Config::DEFAULT_TIMEOUT_SECONDS;

        return [
            'allow_redirects' => false,
            'verify' => true,
            'timeout' => $timeout,
            'connect_timeout' => $timeout,
            'read_timeout' => $timeout,
            'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
        ];
    }

    public function getRequestException(Response $response, ?Throwable $_senderException): Throwable
    {
        return ExceptionMapper::map($response);
    }

    /**
     * @param  callable(Throwable, Request): bool|null  $handleRetry
     */
    #[\Override]
    public function send(Request $request, ?MockClient $mockClient = null, ?callable $handleRetry = null): Response
    {
        try {
            return parent::send($request, $mockClient, $handleRetry);
        } catch (FatalRequestException) {
            throw new TransportException('The Creem API request could not be completed.');
        }
    }
}
