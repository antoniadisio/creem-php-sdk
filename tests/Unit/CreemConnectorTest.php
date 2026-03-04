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
use RuntimeException;
use Saloon\Enums\Method;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;

test('connector builds expected headers and request configuration', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123', Environment::Test, null, 12.5, 'integration-suite'));
    $pendingRequest = $connector->createPendingRequest(creemConnectorTestRequest());
    $psrRequest = $pendingRequest->createPsrRequest();

    $this->assertSame('https://test-api.creem.io/v1/ping', (string) $psrRequest->getUri());
    $this->assertSame('application/json', $psrRequest->getHeaderLine('Accept'));
    $this->assertSame('application/json', $psrRequest->getHeaderLine('Content-Type'));
    $this->assertSame('sk_test_123', $psrRequest->getHeaderLine('x-api-key'));
    $this->assertStringStartsWith('creem-php-sdk/', $psrRequest->getHeaderLine('User-Agent'));
    $this->assertStringContainsString('integration-suite', $psrRequest->getHeaderLine('User-Agent'));
    $this->assertEqualsWithDelta(12.5, $pendingRequest->config()->all()['timeout'], PHP_FLOAT_EPSILON);
});

test('invalid json is normalized to a transport exception', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $response = $connector->send(
        creemConnectorTestRequest(),
        new MockClient([
            MockResponse::make('{"broken"', 200, ['Content-Type' => 'application/json']),
        ]),
    );

    expect(static function () use ($response): void {
        ResponseDecoder::decode($response);
    })->toThrow(TransportException::class);
});

test('transport failures are wrapped', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $mockResponse = MockResponse::make()->throw(
        static fn (PendingRequest $pendingRequest): FatalRequestException => new FatalRequestException(
            new RuntimeException('Socket closed'),
            $pendingRequest,
        ),
    );

    expect(static function () use ($connector, $mockResponse): void {
        $connector->send(creemConnectorTestRequest(), new MockClient([$mockResponse]));
    })->toThrow(TransportException::class);
});

foreach (creemConnectorResponseFailureMappings() as $dataset => [$response, $expectedException]) {
    test("http failures are mapped to typed exceptions ({$dataset})", function () use ($response, $expectedException): void {
        $connector = new CreemConnector(new Config('sk_test_123'));

        expect(static function () use ($connector, $response): void {
            $connector->send(creemConnectorTestRequest(), new MockClient([$response]));
        })->toThrow($expectedException);
    });
}

function creemConnectorTestRequest(): Request
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

/**
 * @return array<string, array{0: MockResponse, 1: class-string<\Throwable>}>
 */
function creemConnectorResponseFailureMappings(): array
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
