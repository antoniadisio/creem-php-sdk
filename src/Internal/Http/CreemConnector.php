<?php

declare(strict_types=1);

namespace Creem\Internal\Http;

use Creem\Config;
use Creem\Exception\TransportException;
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
     * @return array<string, float>
     */
    protected function defaultConfig(): array
    {
        if ($this->sdkConfig->timeout() === null) {
            return [];
        }

        return ['timeout' => $this->sdkConfig->timeout()];
    }

    public function getRequestException(Response $response, ?Throwable $senderException): Throwable
    {
        return ExceptionMapper::map($response, $senderException);
    }

    /**
     * @param  callable(Throwable, Request): bool|null  $handleRetry
     */
    public function send(Request $request, ?MockClient $mockClient = null, ?callable $handleRetry = null): Response
    {
        try {
            return parent::send($request, $mockClient, $handleRetry);
        } catch (FatalRequestException $exception) {
            throw new TransportException('The Creem API request could not be completed.', null, [], $exception);
        }
    }
}
