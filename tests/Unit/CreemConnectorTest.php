<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Config;
use Creem\Environment;
use Creem\Exception\AuthenticationException;
use Creem\Exception\NotFoundException;
use Creem\Exception\RateLimitException;
use Creem\Exception\ServerException;
use Creem\Exception\TransportException;
use Creem\Exception\ValidationException;
use Creem\Internal\Http\CreemConnector;
use Creem\Internal\Http\ResponseDecoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Saloon\Enums\Method;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;

final class CreemConnectorTest extends TestCase
{
    public function test_connector_builds_expected_headers_and_request_configuration(): void
    {
        $connector = new CreemConnector(new Config('sk_test_123', Environment::Test, null, 12.5, 'integration-suite'));
        $pendingRequest = $connector->createPendingRequest($this->request());
        $psrRequest = $pendingRequest->createPsrRequest();

        self::assertSame('https://test-api.creem.io/v1/ping', (string) $psrRequest->getUri());
        self::assertSame('application/json', $psrRequest->getHeaderLine('Accept'));
        self::assertSame('application/json', $psrRequest->getHeaderLine('Content-Type'));
        self::assertSame('sk_test_123', $psrRequest->getHeaderLine('x-api-key'));
        self::assertStringStartsWith('creem-php-sdk/', $psrRequest->getHeaderLine('User-Agent'));
        self::assertStringContainsString('integration-suite', $psrRequest->getHeaderLine('User-Agent'));
        self::assertSame(12.5, $pendingRequest->config()->all()['timeout']);
    }

    /**
     * @param  class-string<\Throwable>  $expectedException
     */
    #[DataProvider('responseFailureMappings')]
    public function test_http_failures_are_mapped_to_typed_exceptions(
        MockResponse $response,
        string $expectedException,
    ): void {
        $connector = new CreemConnector(new Config('sk_test_123'));

        $this->expectException($expectedException);

        $connector->send($this->request(), new MockClient([$response]));
    }

    /**
     * @return array<string, array{0: MockResponse, 1: class-string<\Throwable>}>
     */
    public static function responseFailureMappings(): array
    {
        return [
            'unauthorized' => [
                MockResponse::make(['message' => 'Unauthorized'], 401),
                AuthenticationException::class,
            ],
            'forbidden' => [
                MockResponse::make(['message' => 'Forbidden'], 403),
                AuthenticationException::class,
            ],
            'not_found' => [
                MockResponse::make(['message' => 'Missing'], 404),
                NotFoundException::class,
            ],
            'validation_status' => [
                MockResponse::make(['message' => 'Invalid'], 422),
                ValidationException::class,
            ],
            'validation_errors' => [
                MockResponse::make(['errors' => ['name' => ['Name is required.']]], 400),
                ValidationException::class,
            ],
            'rate_limit' => [
                MockResponse::make(['message' => 'Slow down'], 429),
                RateLimitException::class,
            ],
            'server_error' => [
                MockResponse::make('Internal server error', 500),
                ServerException::class,
            ],
        ];
    }

    public function test_invalid_json_is_normalized_to_a_transport_exception(): void
    {
        $connector = new CreemConnector(new Config('sk_test_123'));
        $response = $connector->send(
            $this->request(),
            new MockClient([
                MockResponse::make('{"broken"', 200, ['Content-Type' => 'application/json']),
            ]),
        );

        $this->expectException(TransportException::class);

        ResponseDecoder::decode($response);
    }

    public function test_transport_failures_are_wrapped(): void
    {
        $connector = new CreemConnector(new Config('sk_test_123'));
        $mockResponse = MockResponse::make()->throw(
            static fn (PendingRequest $pendingRequest): FatalRequestException => new FatalRequestException(
                new RuntimeException('Socket closed'),
                $pendingRequest,
            ),
        );

        $this->expectException(TransportException::class);

        $connector->send($this->request(), new MockClient([$mockResponse]));
    }

    private function request(): Request
    {
        return new class extends Request
        {
            protected Method $method = Method::GET;

            public function resolveEndpoint(): string
            {
                return '/v1/ping';
            }
        };
    }
}
