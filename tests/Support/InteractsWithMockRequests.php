<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Support;

use Antoniadisio\Creem\Config;
use Antoniadisio\Creem\Internal\Http\CreemConnector;
use JsonException;
use Psr\Http\Message\RequestInterface;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;

use function is_string;
use function json_decode;
use function parse_str;
use function parse_url;

trait InteractsWithMockRequests
{
    public function connector(MockClient $mockClient): CreemConnector
    {
        return new CreemConnector(new Config('sk_test_123'))->withMockClient($mockClient);
    }

    /**
     * @param  array<string, string>  $expectedQuery
     * @param  array<string, mixed>|null  $expectedJson
     * @param  array<string, string>  $expectedHeaders
     */
    public function assertRequest(
        MockClient $mockClient,
        Method $expectedMethod,
        string $expectedPath,
        array $expectedQuery = [],
        ?array $expectedJson = null,
        array $expectedHeaders = [],
    ): void {
        $pendingRequest = $mockClient->getLastPendingRequest();

        $this->assertInstanceOf(\Saloon\Http\PendingRequest::class, $pendingRequest);
        $this->assertSame($expectedMethod, $pendingRequest->getMethod());

        $psrRequest = $pendingRequest->createPsrRequest();

        $this->assertSame($expectedPath, $this->path($psrRequest));
        $this->assertSame($expectedQuery, $this->query($psrRequest));

        foreach ($expectedHeaders as $header => $value) {
            $this->assertSame($value, $psrRequest->getHeaderLine($header));
        }

        if ($expectedJson === null) {
            return;
        }

        $this->assertSame($expectedJson, $this->jsonBody($psrRequest));
    }

    /**
     * @return array<string, string>
     */
    public function query(RequestInterface $request): array
    {
        $query = parse_url((string) $request->getUri(), PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            return [];
        }

        parse_str($query, $params);

        /** @var array<string, string> $params */
        return $params;
    }

    public function path(RequestInterface $request): string
    {
        return $request->getUri()->getPath();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function jsonBody(RequestInterface $request): array
    {
        /** @var array<string, mixed> */
        return json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}
