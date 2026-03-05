<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Config;
use Creem\Environment;
use Creem\Exception\AuthenticationException;
use Creem\Exception\CreemException;
use Creem\Exception\NotFoundException;
use Creem\Exception\RateLimitException;
use Creem\Exception\ServerException;
use Creem\Exception\TransportException;
use Creem\Exception\ValidationException;
use Creem\Internal\Http\CreemConnector;
use Creem\Internal\Http\Requests\Discounts\DeleteDiscountRequest;
use Creem\Internal\Http\Requests\Products\CreateProductRequest as CreateProductOperation;
use Creem\Internal\Http\Requests\Subscriptions\CancelSubscriptionRequest;
use Creem\Internal\Http\Requests\Subscriptions\PauseSubscriptionRequest;
use Creem\Internal\Http\Requests\Subscriptions\ResumeSubscriptionRequest;
use Creem\Internal\Http\Requests\Subscriptions\UpdateSubscriptionRequest;
use Creem\Internal\Http\Requests\Subscriptions\UpgradeSubscriptionRequest;
use Creem\Internal\Http\ResponseDecoder;
use InvalidArgumentException;
use RuntimeException;
use Saloon\Enums\Method;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;

test('connector builds expected headers and hardened request configuration', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123', Environment::Test, null, 12.5, 'integration-suite'));
    $pendingRequest = $connector->createPendingRequest(creemConnectorTestRequest());
    $psrRequest = $pendingRequest->createPsrRequest();
    $requestConfig = $pendingRequest->config()->all();

    expect((string) $psrRequest->getUri())->toBe('https://test-api.creem.io/v1/ping')
        ->and($psrRequest->getHeaderLine('Accept'))->toBe('application/json')
        ->and($psrRequest->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and($psrRequest->getHeaderLine('x-api-key'))->toBe('sk_test_123')
        ->and($psrRequest->getHeaderLine('User-Agent'))->toStartWith('creem-php-sdk/')
        ->and($psrRequest->getHeaderLine('User-Agent'))->toContain('integration-suite')
        ->and($requestConfig['allow_redirects'])->toBeFalse()
        ->and($requestConfig['verify'])->toBeTrue()
        ->and($requestConfig['timeout'])->toBe(12.5)
        ->and($requestConfig['connect_timeout'])->toBe(12.5)
        ->and($requestConfig['read_timeout'])->toBe(12.5)
        ->and($requestConfig['crypto_method'])->toBe(STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
});

test('connector applies the default timeout when none is configured', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $pendingRequest = $connector->createPendingRequest(creemConnectorTestRequest());
    $requestConfig = $pendingRequest->config()->all();

    expect($requestConfig['timeout'])->toBe(Config::DEFAULT_TIMEOUT_SECONDS)
        ->and($requestConfig['connect_timeout'])->toBe(Config::DEFAULT_TIMEOUT_SECONDS)
        ->and($requestConfig['read_timeout'])->toBe(Config::DEFAULT_TIMEOUT_SECONDS);
});

test('mutating requests reject invalid idempotency keys', function (): void {
    expect(static fn (): CreateProductOperation => new CreateProductOperation([], " \r\n "))
        ->toThrow(InvalidArgumentException::class, 'The Creem idempotency key cannot be blank.');
});

test('mutating endpoint requests normalize identifiers before endpoint resolution', function (): void {
    expect(new CancelSubscriptionRequest('  sub_123  ')->resolveEndpoint())
        ->toBe('/v1/subscriptions/sub_123/cancel')
        ->and(new UpdateSubscriptionRequest('  sub_123  ')->resolveEndpoint())
        ->toBe('/v1/subscriptions/sub_123')
        ->and(new UpgradeSubscriptionRequest('  sub_123  ')->resolveEndpoint())
        ->toBe('/v1/subscriptions/sub_123/upgrade')
        ->and(new PauseSubscriptionRequest('  sub_123  ')->resolveEndpoint())
        ->toBe('/v1/subscriptions/sub_123/pause')
        ->and(new ResumeSubscriptionRequest('  sub_123  ')->resolveEndpoint())
        ->toBe('/v1/subscriptions/sub_123/resume')
        ->and(new DeleteDiscountRequest('  disc_123  ')->resolveEndpoint())
        ->toBe('/v1/discounts/disc_123/delete');
});

foreach (invalidMutatingPathIdentifierFactories() as $dataset => [$factory, $message]) {
    test("mutating endpoint requests reject invalid path identifiers ({$dataset})", function () use ($factory, $message): void {
        expect($factory)->toThrow(InvalidArgumentException::class, $message);
    });
}

foreach (blankResponseBodies() as $dataset => [$body]) {
    test("response decoder returns empty payloads for blank bodies ({$dataset})", function () use ($body): void {
        $response = creemConnectorSuccessResponse(MockResponse::make($body, 200, ['Content-Type' => 'application/json']));

        expect(ResponseDecoder::decode($response))->toBe([]);
    });
}

foreach (nonObjectJsonPayloads() as $dataset => [$body]) {
    test("response decoder rejects non object json payloads ({$dataset})", function () use ($body): void {
        $response = creemConnectorSuccessResponse(MockResponse::make($body, 200, ['Content-Type' => 'application/json']));

        expect(static fn (): array => ResponseDecoder::decode($response))
            ->toThrow(TransportException::class, 'The Creem API returned an unexpected JSON payload shape.');
    });
}

test('invalid json is normalized to a transport exception', function (): void {
    $response = creemConnectorSuccessResponse(
        MockResponse::make('{"broken"', 200, ['Content-Type' => 'application/json']),
    );

    expect(static fn (): array => ResponseDecoder::decode($response))
        ->toThrow(TransportException::class, 'The Creem API returned an invalid JSON response.');
});

test('transport failures are wrapped without leaking sender exception internals', function (): void {
    $connector = new CreemConnector(new Config('sk_test_very_secret_123'));
    $mockResponse = MockResponse::make()->throw(
        static fn (PendingRequest $pendingRequest): FatalRequestException => new FatalRequestException(
            new RuntimeException('Socket closed for sk_test_very_secret_123'),
            $pendingRequest,
        ),
    );

    $exception = captureCreemException(
        static fn (): Response => $connector->send(creemConnectorTestRequest(), new MockClient([$mockResponse])),
    );

    expect($exception)->toBeInstanceOf(TransportException::class)
        ->and($exception?->getMessage())->toBe('The Creem API request could not be completed.')
        ->and($exception?->getMessage())->not->toContain('sk_test_very_secret_123')
        ->and($exception?->statusCode())->toBeNull()
        ->and($exception?->context())->toBe([])
        ->and($exception?->getPrevious())->toBeNull();
});

test('plain text error bodies redact sensitive token patterns', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $exception = captureCreemException(
        static fn (): Response => $connector->send(
            creemConnectorTestRequest(),
            new MockClient([
                MockResponse::make('Rejected sk_live_secret creem_live_secret whsec_secret', 500),
            ]),
        ),
    );

    expect($exception)->toBeInstanceOf(ServerException::class)
        ->and($exception?->getMessage())->toBe('Rejected [redacted] [redacted] [redacted]')
        ->and($exception?->context())->toBe(['body' => 'Rejected [redacted] [redacted] [redacted]'])
        ->and($exception?->getMessage())->not->toContain('sk_live_secret')
        ->and($exception?->getMessage())->not->toContain('creem_live_secret')
        ->and($exception?->getMessage())->not->toContain('whsec_secret')
        ->and($exception?->getPrevious())->toBeNull();
});

test('json error messages and context redact sensitive token patterns', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $exception = captureCreemException(
        static fn (): Response => $connector->send(
            creemConnectorTestRequest(),
            new MockClient([
                MockResponse::make([
                    'message' => 'Invalid key sk_live_secret',
                    'detail' => 'Credential creem_live_secret is invalid',
                    'errors' => [
                        [
                            'detail' => 'Webhook secret whsec_secret is invalid',
                        ],
                    ],
                ], 422),
            ]),
        ),
    );

    expect($exception)->toBeInstanceOf(ValidationException::class)
        ->and($exception?->getMessage())->toBe('Invalid key [redacted]')
        ->and($exception?->context())->toBe([
            'message' => 'Invalid key [redacted]',
            'detail' => 'Credential [redacted] is invalid',
            'errors' => [
                [
                    'detail' => 'Webhook secret [redacted] is invalid',
                ],
            ],
        ])
        ->and($exception?->getPrevious())->toBeNull();
});

foreach (creemConnectorResponseFailureMappings() as $dataset => [$response, $expectedException, $expectedMessage, $expectedStatus, $expectedContext]) {
    test("http failures are mapped to typed exceptions with preserved context ({$dataset})", function () use (
        $response,
        $expectedException,
        $expectedMessage,
        $expectedStatus,
        $expectedContext,
    ): void {
        $connector = new CreemConnector(new Config('sk_test_123'));
        $exception = captureCreemException(
            static fn (): Response => $connector->send(creemConnectorTestRequest(), new MockClient([$response])),
        );

        expect($exception)->toBeInstanceOf(CreemException::class)
            ->and($exception)->toBeInstanceOf($expectedException)
            ->and($exception?->getMessage())->toBe($expectedMessage)
            ->and($exception?->statusCode())->toBe($expectedStatus)
            ->and($exception?->context())->toBe($expectedContext);

        if ($exception instanceof ValidationException && array_key_exists('errors', $expectedContext)) {
            expect($exception->errors())->toBe($expectedContext['errors']);
        }

        if ($exception instanceof RateLimitException) {
            expect($exception->retryAfterSeconds())->toBe($expectedContext['retry_after_seconds'] ?? null);
        }
    });
}

test('generic client errors map to the base exception type', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $exception = captureCreemException(
        static fn (): Response => $connector->send(
            creemConnectorTestRequest(),
            new MockClient([
                MockResponse::make(['detail' => 'Conflict'], 409),
            ]),
        ),
    );

    expect($exception)->toBeInstanceOf(CreemException::class)
        ->and($exception)->not->toBeInstanceOf(AuthenticationException::class)
        ->and($exception)->not->toBeInstanceOf(NotFoundException::class)
        ->and($exception)->not->toBeInstanceOf(ValidationException::class)
        ->and($exception)->not->toBeInstanceOf(RateLimitException::class)
        ->and($exception)->not->toBeInstanceOf(ServerException::class)
        ->and($exception?->getMessage())->toBe('Conflict')
        ->and($exception?->statusCode())->toBe(409)
        ->and($exception?->context())->toBe(['detail' => 'Conflict']);
});

test('nested validation errors resolve useful messages', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $errors = [
        [
            'meta' => [
                'detail' => 'Nested error message',
            ],
        ],
    ];
    $exception = captureCreemException(
        static fn (): Response => $connector->send(
            creemConnectorTestRequest(),
            new MockClient([
                MockResponse::make(['errors' => $errors], 400),
            ]),
        ),
    );

    expect($exception)->toBeInstanceOf(ValidationException::class)
        ->and($exception?->getMessage())->toBe('Nested error message')
        ->and($exception?->statusCode())->toBe(400)
        ->and($exception?->context())->toBe(['errors' => $errors]);

    if ($exception instanceof ValidationException) {
        expect($exception->errors())->toBe($errors);
    }
});

test('deeply nested validation errors stop traversing after the recursion guard', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $errors = ['level_1' => ['level_2' => ['level_3' => ['level_4' => ['level_5' => ['level_6' => ['detail' => 'Too deep']]]]]]];
    $exception = captureCreemException(
        static fn (): Response => $connector->send(
            creemConnectorTestRequest(),
            new MockClient([
                MockResponse::make(['errors' => $errors], 400),
            ]),
        ),
    );

    expect($exception)->toBeInstanceOf(ValidationException::class)
        ->and($exception?->getMessage())->toBe('The Creem API request failed with status 400.')
        ->and($exception?->context())->toBe([
            'errors' => [
                'level_1' => [
                    'level_2' => [
                        'level_3' => [
                            'level_4' => '[truncated]',
                        ],
                    ],
                ],
            ],
        ]);
});

test('error contexts are sanitized before they are attached to exceptions', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $exception = captureCreemException(
        static fn (): Response => $connector->send(
            creemConnectorTestRequest(),
            new MockClient([
                MockResponse::make([
                    'message' => 'Invalid customer data',
                    'email' => 'customer@example.com',
                    'errors' => [
                        'email' => ['Email is invalid.'],
                        'submitted_value' => 'customer@example.com',
                    ],
                ], 422),
            ]),
        ),
    );

    expect($exception)->toBeInstanceOf(ValidationException::class)
        ->and($exception?->context())->toBe([
            'message' => 'Invalid customer data',
            'errors' => [
                'email' => ['Email is invalid.'],
                'submitted_value' => '[redacted]',
            ],
        ]);
});

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
 * @return array<string, array{0: string}>
 */
function blankResponseBodies(): array
{
    return [
        'empty string' => [''],
        'whitespace only' => [" \n\t "],
    ];
}

/**
 * @return array<string, array{0: string}>
 */
function nonObjectJsonPayloads(): array
{
    return [
        'json list' => ['[]'],
        'json scalar' => ['"ok"'],
    ];
}

/**
 * @return array<string, array{
 *     0: MockResponse,
 *     1: class-string<CreemException>,
 *     2: string,
 *     3: int,
 *     4: array<string, mixed>
 * }>
 */
function creemConnectorResponseFailureMappings(): array
{
    return [
        'unauthorized' => [
            MockResponse::make(['message' => 'Unauthorized'], 401),
            AuthenticationException::class,
            'Unauthorized',
            401,
            ['message' => 'Unauthorized'],
        ],
        'forbidden' => [
            MockResponse::make(['message' => 'Forbidden'], 403),
            AuthenticationException::class,
            'Forbidden',
            403,
            ['message' => 'Forbidden'],
        ],
        'not_found' => [
            MockResponse::make(['message' => 'Missing'], 404),
            NotFoundException::class,
            'Missing',
            404,
            ['message' => 'Missing'],
        ],
        'validation_status' => [
            MockResponse::make(['message' => 'Invalid'], 422),
            ValidationException::class,
            'Invalid',
            422,
            ['message' => 'Invalid'],
        ],
        'validation_errors' => [
            MockResponse::make(['errors' => ['name' => ['Name is required.']]], 400),
            ValidationException::class,
            'Name is required.',
            400,
            ['errors' => ['name' => ['Name is required.']]],
        ],
        'rate_limit' => [
            MockResponse::make(['message' => 'Slow down'], 429, ['Retry-After' => '7']),
            RateLimitException::class,
            'Slow down',
            429,
            ['message' => 'Slow down', 'retry_after_seconds' => 7],
        ],
        'server_error' => [
            MockResponse::make('Internal server error', 500),
            ServerException::class,
            'Internal server error',
            500,
            ['body' => 'Internal server error'],
        ],
    ];
}

function creemConnectorSuccessResponse(MockResponse $mockResponse): Response
{
    $connector = new CreemConnector(new Config('sk_test_123'));

    return $connector->send(creemConnectorTestRequest(), new MockClient([$mockResponse]));
}

function captureCreemException(callable $callback): ?CreemException
{
    try {
        $callback();
    } catch (CreemException $exception) {
        return $exception;
    }

    return null;
}

/**
 * @return array<string, array{0: callable(): mixed, 1: string}>
 */
function invalidMutatingPathIdentifierFactories(): array
{
    return [
        'subscription path traversal with slash' => [
            static fn (): CancelSubscriptionRequest => new CancelSubscriptionRequest('sub_123/upgrade'),
            'The subscription ID cannot contain reserved URI characters or control characters.',
        ],
        'subscription query injection' => [
            static fn (): PauseSubscriptionRequest => new PauseSubscriptionRequest('sub_123?mode=cancel'),
            'The subscription ID cannot contain reserved URI characters or control characters.',
        ],
        'subscription fragment injection' => [
            static fn (): ResumeSubscriptionRequest => new ResumeSubscriptionRequest('sub_123#admin'),
            'The subscription ID cannot contain reserved URI characters or control characters.',
        ],
        'subscription percent encoding probe' => [
            static fn (): UpdateSubscriptionRequest => new UpdateSubscriptionRequest('sub%2F123'),
            'The subscription ID cannot contain reserved URI characters or control characters.',
        ],
        'subscription unsupported punctuation' => [
            static fn (): UpgradeSubscriptionRequest => new UpgradeSubscriptionRequest('sub:123'),
            'The subscription ID contains unsupported characters. Allowed characters are letters, numbers, ".", "_", and "-".',
        ],
        'discount reserved path separator' => [
            static fn (): DeleteDiscountRequest => new DeleteDiscountRequest('disc_123/delete'),
            'The discount ID cannot contain reserved URI characters or control characters.',
        ],
        'discount unsupported whitespace' => [
            static fn (): DeleteDiscountRequest => new DeleteDiscountRequest('disc 123'),
            'The discount ID contains unsupported characters. Allowed characters are letters, numbers, ".", "_", and "-".',
        ],
        'blank subscription id' => [
            static fn (): CancelSubscriptionRequest => new CancelSubscriptionRequest('  '),
            'The subscription ID cannot be blank.',
        ],
        'subscription single dot segment (cancel)' => [
            static fn (): CancelSubscriptionRequest => new CancelSubscriptionRequest('.'),
            'The subscription ID cannot be "." or "..".',
        ],
        'subscription double dot segment (update)' => [
            static fn (): UpdateSubscriptionRequest => new UpdateSubscriptionRequest('..'),
            'The subscription ID cannot be "." or "..".',
        ],
        'subscription single dot segment (upgrade)' => [
            static fn (): UpgradeSubscriptionRequest => new UpgradeSubscriptionRequest('.'),
            'The subscription ID cannot be "." or "..".',
        ],
        'subscription double dot segment (pause)' => [
            static fn (): PauseSubscriptionRequest => new PauseSubscriptionRequest('..'),
            'The subscription ID cannot be "." or "..".',
        ],
        'subscription single dot segment (resume)' => [
            static fn (): ResumeSubscriptionRequest => new ResumeSubscriptionRequest('.'),
            'The subscription ID cannot be "." or "..".',
        ],
        'blank discount id' => [
            static fn (): DeleteDiscountRequest => new DeleteDiscountRequest('  '),
            'The discount ID cannot be blank.',
        ],
        'discount single dot segment' => [
            static fn (): DeleteDiscountRequest => new DeleteDiscountRequest('.'),
            'The discount ID cannot be "." or "..".',
        ],
        'discount double dot segment' => [
            static fn (): DeleteDiscountRequest => new DeleteDiscountRequest('..'),
            'The discount ID cannot be "." or "..".',
        ],
    ];
}
